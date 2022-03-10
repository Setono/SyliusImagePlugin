<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Command;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Psr\EventDispatcher\EventDispatcherInterface;
use Setono\DoctrineObjectManagerTrait\ORM\ORMManagerTrait;
use Setono\SyliusImagePlugin\Config\ImageResource;
use Setono\SyliusImagePlugin\Config\VariantCollectionInterface;
use Setono\SyliusImagePlugin\Event\ProcessingStartedEvent;
use Setono\SyliusImagePlugin\Message\Command\ProcessImage;
use Setono\SyliusImagePlugin\Model\ImageInterface;
use Setono\SyliusImagePlugin\Model\VariantConfigurationInterface;
use Setono\SyliusImagePlugin\Provider\ProcessableImageResourceProviderInterface;
use Setono\SyliusImagePlugin\Repository\VariantConfigurationRepositoryInterface;
use Setono\SyliusImagePlugin\Synchronizer\VariantConfigurationSynchronizerInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
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

    private ProcessableImageResourceProviderInterface $processableImageResourceProvider;

    private MessageBusInterface $commandBus;

    private VariantCollectionInterface $variantCollection;

    private EventDispatcherInterface $eventDispatcher;

    private VariantConfigurationRepositoryInterface $variantConfigurationRepository;

    private VariantConfigurationSynchronizerInterface $variantConfigurationSynchronizer;

    private int $maximumNumberOfTries;

    /** @var ImageResource[] */
    private array $processableImageResources = [];

    private ?int $limitPerResource = null;

    public const OPTION_SYNC_CONFIGURATION = 'sync-configuration';

    public const OPTION_LIMIT = 'limit';

    public const ARGUMENT_RESOURCES = 'resources';

    public function __construct(
        ManagerRegistry $managerRegistry,
        ProcessableImageResourceProviderInterface $processableImageResourceProvider,
        MessageBusInterface $commandBus,
        VariantCollectionInterface $variantCollection,
        EventDispatcherInterface $eventDispatcher,
        VariantConfigurationRepositoryInterface $variantConfigurationRepository,
        VariantConfigurationSynchronizerInterface $variantConfigurationSynchronizer,
        int $maximumNumberOfTries = 10
    ) {
        parent::__construct();

        $this->managerRegistry = $managerRegistry;
        $this->processableImageResourceProvider = $processableImageResourceProvider;
        $this->commandBus = $commandBus;
        $this->variantCollection = $variantCollection;
        $this->eventDispatcher = $eventDispatcher;
        $this->variantConfigurationRepository = $variantConfigurationRepository;
        $this->variantConfigurationSynchronizer = $variantConfigurationSynchronizer;
        $this->maximumNumberOfTries = $maximumNumberOfTries;
    }

    protected function configure(): void
    {
        $this->addOption(self::OPTION_SYNC_CONFIGURATION, null, InputOption::VALUE_NONE, 'Sync plugin configuration with database');
        $this->addOption(SynchronizeVariantConfigurationCommand::OPTION_SKIP_SETUP, null, InputOption::VALUE_NONE, sprintf('Skip setup - only applicable if \'--%s\' flag is set', self::OPTION_SYNC_CONFIGURATION));
        $this->addOption(self::OPTION_LIMIT, 'l', InputOption::VALUE_REQUIRED, 'Limit for how many images to process per resource. Default: unlimited');

        $this->addArgument(self::ARGUMENT_RESOURCES, InputArgument::IS_ARRAY, 'Specify one or more resources to process. Ex: \'sylius.product_image sylius.taxon_image\'. If nothing is specified all available image resources are processed.');

        $this->setHelp(sprintf(<<<'EOF'
The <info>%%command.name%%</> command fetches the newest configuration from the database and processes all images that
don't have the newest configuration.

You can automatically sync your plugin configuration with the database by using the <comment>--%1$s</comment> flag:

  <info>php %%command.full_name%% --%1$s</>

This flag will do the exact same as the <info>%2$s</> command.
EOF, self::OPTION_SYNC_CONFIGURATION, SynchronizeVariantConfigurationCommand::getDefaultName() ?? 'sync-variant-configuration'));
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);

        $limitPerResource = $input->getOption(self::OPTION_LIMIT);
        Assert::nullOrIntegerish($limitPerResource);
        if ($limitPerResource !== null) {
            $limit = (int) $limitPerResource;
            Assert::greaterThanEq($limit, 0, 'The limit can\'t be negative');
            $this->limitPerResource = $limit;
            if ($limit === 0) {
                $this->io->warning('Limit=0 means no images will be processed');
            }
        }

        $availableResources = [];
        foreach ($this->processableImageResourceProvider->getResources() as $imageResource) {
            $availableResources[$imageResource->resourceKey] = $imageResource;
        }

        /** @var string[]|object $chosenResources */
        $chosenResources = $input->getArgument(self::ARGUMENT_RESOURCES);

        if (!is_array($chosenResources) || empty($chosenResources)) {
            $this->processableImageResources = $availableResources;
        } else {
            foreach ($chosenResources as $resourceKey) {
                if (isset($availableResources[$resourceKey])) {
                    $this->processableImageResources[$resourceKey] = $availableResources[$resourceKey];
                } else {
                    $this->io->warning(sprintf('The requested resource \'%s\' is not available', $resourceKey));
                }
            }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->variantCollection->isEmpty()) {
            $this->io->writeln('No variants configured');

            return 0;
        }

        $syncConfiguration = true === $input->getOption(self::OPTION_SYNC_CONFIGURATION);

        if ([] === $this->processableImageResources) {
            $this->io->writeln('No resources selected/available for processing');

            return 0;
        }

        if ($syncConfiguration) {
            $runSetup = true !== $input->getOption(SynchronizeVariantConfigurationCommand::OPTION_SKIP_SETUP);
            $synchronizationResult = $this->variantConfigurationSynchronizer->synchronize($runSetup);
            SynchronizeVariantConfigurationCommand::reportSynchronizationResult($synchronizationResult, $this->io);
        }

        $variantConfiguration = $this->variantConfigurationRepository->findNewest();
        if (null === $variantConfiguration) {
            $this->io->writeln('No variant configuration saved in the database. Run with --sync-configuration instead.');

            return 0;
        }

        $variantCollection = $variantConfiguration->getVariantCollection();
        Assert::notNull($variantCollection);

        $this->eventDispatcher->dispatch(new ProcessingStartedEvent($variantCollection));

        foreach ($this->processableImageResources as $processableResource) {
            $this->processResource($processableResource, $variantConfiguration);
        }

        return 0;
    }

    private function processResource(ImageResource $resource, VariantConfigurationInterface $variantConfiguration): void
    {
        $this->io->section(sprintf('Processing resource: %s', $resource->className));

        $manager = $this->getManager($resource->className);

        /** @var ObjectRepository|EntityRepository $repository */
        $repository = $manager->getRepository($resource->className);
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
            ->setParameter('processingStates', [ImageInterface::PROCESSING_STATE_INITIAL, ImageInterface::PROCESSING_STATE_FAILED, ImageInterface::PROCESSING_STATE_PROCESSED])
        ;

        do {
            if ($this->limitPerResource !== null) {
                $remaining = $this->limitPerResource - $firstResult;
                $maxResults = min($maxResults, $remaining);
            }

            $qb->setMaxResults($maxResults);
            $qb->setFirstResult($firstResult);

            $images = $qb->getQuery()->getResult();
            Assert::isArray($images);

            /** @var ImageInterface $image */
            foreach ($images as $image) {
                yield $image;
            }

            $firstResult += $maxResults;

            $manager->clear();
        } while ([] !== $images && ($this->limitPerResource === null || $firstResult < $this->limitPerResource));
    }
}
