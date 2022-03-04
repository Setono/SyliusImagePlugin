<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Command;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Psr\EventDispatcher\EventDispatcherInterface;
use Setono\DoctrineObjectManagerTrait\ORM\ORMManagerTrait;
use Setono\SyliusImagePlugin\Config\VariantCollectionInterface;
use Setono\SyliusImagePlugin\Event\ProcessingStartedEvent;
use Setono\SyliusImagePlugin\Message\Command\ProcessImage;
use Setono\SyliusImagePlugin\Model\ImageInterface;
use Setono\SyliusImagePlugin\Model\VariantConfigurationInterface;
use Setono\SyliusImagePlugin\Provider\ProcessableResourceProviderInterface;
use Setono\SyliusImagePlugin\Repository\VariantConfigurationRepositoryInterface;
use Setono\SyliusImagePlugin\Synchronizer\VariantConfigurationSynchronizerInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

final class ProcessCommand extends Command
{
    use ORMManagerTrait;

    protected static $defaultName = 'setono:sylius-image:process';

    /** @var string|null */
    protected static $defaultDescription = 'Processes all image variants';

    /**
     * It is set in the initialize method which is called before the execute method
     *
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private SymfonyStyle $io;

    private ProcessableResourceProviderInterface $processableResourceProvider;

    private MessageBusInterface $commandBus;

    private VariantCollectionInterface $variantCollection;

    private EventDispatcherInterface $eventDispatcher;

    private VariantConfigurationRepositoryInterface $variantConfigurationRepository;

    private VariantConfigurationSynchronizerInterface $variantConfigurationSynchronizer;

    private int $maximumNumberOfTries;

    public const SYNC_CONFIGURATION_FLAG = 'sync-configuration';

    public function __construct(
        ManagerRegistry $managerRegistry,
        ProcessableResourceProviderInterface $processableResourceProvider,
        MessageBusInterface $commandBus,
        VariantCollectionInterface $variantCollection,
        EventDispatcherInterface $eventDispatcher,
        VariantConfigurationRepositoryInterface $variantConfigurationRepository,
        VariantConfigurationSynchronizerInterface $variantConfigurationSynchronizer,
        int $maximumNumberOfTries = 10
    ) {
        parent::__construct();

        $this->managerRegistry = $managerRegistry;
        $this->processableResourceProvider = $processableResourceProvider;
        $this->commandBus = $commandBus;
        $this->variantCollection = $variantCollection;
        $this->eventDispatcher = $eventDispatcher;
        $this->variantConfigurationRepository = $variantConfigurationRepository;
        $this->variantConfigurationSynchronizer = $variantConfigurationSynchronizer;
        $this->maximumNumberOfTries = $maximumNumberOfTries;
    }

    protected function configure(): void
    {
        $this->addOption(self::SYNC_CONFIGURATION_FLAG, null, InputOption::VALUE_NONE, 'Sync plugin configuration with database');
        $this->addOption(SynchronizeVariantConfigurationCommand::SKIP_SETUP_FLAG, null, InputOption::VALUE_NONE, sprintf('Skip setup - only applicable if \'--%s\' flag is set', self::SYNC_CONFIGURATION_FLAG));

        $syncConfigCommand = SynchronizeVariantConfigurationCommand::getDefaultName();

        $syncConfigFlag = self::SYNC_CONFIGURATION_FLAG; // Heredoc does not allow interpretation of constants

        $this->setHelp(
            <<<EOF
The <info>%command.name%</> command fetches the newest configuration from the database and processes all images that
doesn't have the newest configuration.

You can automatically sync your plugin configuration with the database by using the <comment>--{$syncConfigFlag}</comment> flag:

  <info>php %command.full_name% --{$syncConfigFlag}</>

This flag will do the exact same as the <info>{$syncConfigCommand}</> command.
EOF
        );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->variantCollection->isEmpty()) {
            $this->io->writeln('No variants configured');

            return 0;
        }

        $syncConfiguration = true === $input->getOption(self::SYNC_CONFIGURATION_FLAG);

        $processableResources = $this->processableResourceProvider->getResources();

        if ([] === $processableResources) {
            $this->io->writeln(sprintf('No resources implements the interface %s', ImageInterface::class));

            return 0;
        }

        if ($syncConfiguration) {
            $runSetup = true !== $input->getOption(SynchronizeVariantConfigurationCommand::SKIP_SETUP_FLAG);
            $synchronizationResult = $this->variantConfigurationSynchronizer->synchronize($runSetup);
            SynchronizeVariantConfigurationCommand::reportSynchronizationResult($synchronizationResult, $this->io);

            if ($synchronizationResult->isStopExecution()) {
                $this->io->warning('Execution halted by synchronization');

                return 1;
            }
        }

        $variantConfiguration = $this->variantConfigurationRepository->findNewest();
        if (null === $variantConfiguration) {
            $this->io->writeln('No variant configuration saved in the database. Run with --sync-configuration instead.');

            return 0;
        }

        $variantCollection = $variantConfiguration->getVariantCollection();
        Assert::notNull($variantCollection);

        $this->eventDispatcher->dispatch(new ProcessingStartedEvent($variantCollection));

        foreach ($processableResources as $processableResource) {
            $this->processResource($processableResource, $variantConfiguration);
        }

        return 0;
    }

    /**
     * @param class-string $class
     */
    private function processResource(string $class, VariantConfigurationInterface $variantConfiguration): void
    {
        $this->io->section(sprintf('Processing resource: %s', $class));

        $manager = $this->getManager($class);

        /** @var ObjectRepository|EntityRepository $repository */
        $repository = $manager->getRepository($class);
        Assert::isInstanceOf($repository, EntityRepository::class);

        $i = 0;
        foreach ($this->getImages($repository, $manager, $variantConfiguration) as $image) {
            $this->commandBus->dispatch(ProcessImage::fromImage($image));
            ++$i;
        }

        $this->io->success(sprintf('%d images sent to processing...', $i));
    }

    /**
     * @return iterable<ImageInterface>
     */
    private function getImages(
        EntityRepository $repository,
        ObjectManager $manager,
        VariantConfigurationInterface $variantConfiguration
    ): iterable {
        $firstResult = 0;
        $maxResults = 100;

        $now = new \DateTimeImmutable();

        $qb = $repository->createQueryBuilder('o');
        $qb
            ->andWhere($qb->expr()->orX(
                'o.variantConfiguration is null',
                'o.variantConfiguration != :variantConfiguration',
            ))
            ->andWhere($qb->expr()->orX(
                'o.processingRetryAt is null',
                'o.processingRetryAt <= :now',
            ))
            ->andWhere('o.processingTries <= :maximumNumberOfTries')
            ->andWhere('o.processingState IN (:processingStates)')
            ->setParameter('variantConfiguration', $variantConfiguration)
            ->setParameter('now', $now)
            ->setParameter('maximumNumberOfTries', $this->maximumNumberOfTries)
            ->setParameter('processingStates', [ImageInterface::PROCESSING_STATE_PENDING, ImageInterface::PROCESSING_STATE_FAILED])
            ->setMaxResults($maxResults)
        ;

        do {
            $qb->setFirstResult($firstResult);

            $images = $qb->getQuery()->getResult();
            Assert::isArray($images);

            /** @var ImageInterface $image */
            foreach ($images as $image) {
                yield $image;
            }

            $firstResult += $maxResults;

            $manager->clear();
        } while ([] !== $images);
    }
}
