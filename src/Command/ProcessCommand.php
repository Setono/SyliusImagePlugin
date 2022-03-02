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

    private MessageBusInterface $commandBus;

    private VariantCollectionInterface $variantCollection;

    private EventDispatcherInterface $eventDispatcher;

    private VariantConfigurationRepositoryInterface $variantConfigurationRepository;

    private VariantConfigurationSynchronizerInterface $variantConfigurationSynchronizer;

    /** @var array<string, array{classes: array{model: string}}> */
    private array $resources;

    private int $maximumNumberOfTries;

    /**
     * @param array<string, array{classes: array{model: string}}> $resources
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        MessageBusInterface $commandBus,
        VariantCollectionInterface $variantCollection,
        EventDispatcherInterface $eventDispatcher,
        VariantConfigurationRepositoryInterface $variantConfigurationRepository,
        VariantConfigurationSynchronizerInterface $variantConfigurationSynchronizer,
        array $resources,
        int $maximumNumberOfTries = 10
    ) {
        parent::__construct();

        $this->managerRegistry = $managerRegistry;
        $this->commandBus = $commandBus;
        $this->variantCollection = $variantCollection;
        $this->eventDispatcher = $eventDispatcher;
        $this->variantConfigurationRepository = $variantConfigurationRepository;
        $this->variantConfigurationSynchronizer = $variantConfigurationSynchronizer;
        $this->resources = $resources;
        $this->maximumNumberOfTries = $maximumNumberOfTries;
    }

    protected function configure(): void
    {
        $this->addOption('sync-configuration', null, InputOption::VALUE_NONE, 'Sync plugin configuration with database');

        $this->setHelp(
            <<<'EOF'
The <info>%command.name%</> command fetches the newest configuration from the database and processes all images that
doesn't have the newest configuration.

You can automatically sync your plugin configuration with the database by using the <comment>--sync-configuration</comment> flag:

  <info>php %command.full_name% --sync-configuration</>

This flag will do the exact same as the <info>setono:sylius-image:sync-variant-configuration</> command.
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

        $syncConfiguration = true === $input->getOption('sync-configuration');

        /** @var array<array-key, class-string> $resourcesToProcess */
        $resourcesToProcess = [];

        foreach ($this->resources as $resource) {
            if (!is_a($resource['classes']['model'], ImageInterface::class, true)) {
                continue;
            }

            $resourcesToProcess[] = $resource['classes']['model'];
        }

        if ([] === $resourcesToProcess) {
            $this->io->writeln(sprintf('No resources implements the interface %s', ImageInterface::class));

            return 0;
        }

        if ($syncConfiguration) {
            $this->variantConfigurationSynchronizer->synchronize();
        }

        $variantConfiguration = $this->variantConfigurationRepository->findNewest();
        if (null === $variantConfiguration) {
            $this->io->writeln('No variant configuration saved in the database. Run with --sync-configuration instead.');

            return 0;
        }

        $variantCollection = $variantConfiguration->getVariantCollection();
        Assert::notNull($variantCollection);

        $this->eventDispatcher->dispatch(new ProcessingStartedEvent($variantCollection));

        foreach ($resourcesToProcess as $resourceToProcess) {
            $this->processResource($resourceToProcess, $variantConfiguration);
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
