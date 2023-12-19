<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Command;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Psr\EventDispatcher\EventDispatcherInterface;
use Setono\DoctrineObjectManagerTrait\ORM\ORMManagerTrait;
use Setono\SyliusImagePlugin\Config\ImageResource;
use Setono\SyliusImagePlugin\Config\ImageResourceRegistryInterface;
use Setono\SyliusImagePlugin\Config\PresetRegistryInterface;
use Setono\SyliusImagePlugin\Event\ProcessingStartedEvent;
use Setono\SyliusImagePlugin\Message\Command\ProcessImage;
use Setono\SyliusImagePlugin\Model\ImageInterface;
use Setono\SyliusImagePlugin\Model\PresetConfigurationInterface;
use Setono\SyliusImagePlugin\Repository\PresetConfigurationRepositoryInterface;
use Setono\SyliusImagePlugin\Synchronizer\VariantConfigurationSynchronizerInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

final class ProcessCommand extends Command
{
    use LockableTrait;

    use ORMManagerTrait;

    protected static $defaultName = 'setono:sylius-image:process';

    /** @var string|null */
    protected static $defaultDescription = 'Processes configured image resources';

    /**
     * It is set in the initialize method which is called before the execute method
     *
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private SymfonyStyle $io;

    private ImageResourceRegistryInterface $imageResourceRegistry;

    private MessageBusInterface $commandBus;

    private PresetRegistryInterface $presetRegistry;

    private EventDispatcherInterface $eventDispatcher;

    private PresetConfigurationRepositoryInterface $presetConfigurationRepository;

    private VariantConfigurationSynchronizerInterface $variantConfigurationSynchronizer;

    private int $maximumNumberOfTries;

    /** @var ImageResource[] */
    private array $processableImageResources = [];

    private ?int $limitPerResource = null;

    private ?int $maxPendingImages = null;

    public const OPTION_SYNC_CONFIGURATION = 'sync-configuration';

    public const OPTION_LIMIT = 'limit';

    public const OPTION_MAX_PENDING = 'max-pending';

    public const ARGUMENT_RESOURCES = 'resources';

    public function __construct(
        ManagerRegistry $managerRegistry,
        ImageResourceRegistryInterface $imageResourceRegistry,
        MessageBusInterface $commandBus,
        PresetRegistryInterface $presetRegistry,
        EventDispatcherInterface $eventDispatcher,
        PresetConfigurationRepositoryInterface $presetConfigurationRepository,
        VariantConfigurationSynchronizerInterface $variantConfigurationSynchronizer,
        int $maximumNumberOfTries = 10
    ) {
        parent::__construct();

        $this->managerRegistry = $managerRegistry;
        $this->imageResourceRegistry = $imageResourceRegistry;
        $this->commandBus = $commandBus;
        $this->presetRegistry = $presetRegistry;
        $this->eventDispatcher = $eventDispatcher;
        $this->presetConfigurationRepository = $presetConfigurationRepository;
        $this->variantConfigurationSynchronizer = $variantConfigurationSynchronizer;
        $this->maximumNumberOfTries = $maximumNumberOfTries;
    }

    protected function configure(): void
    {
        $this->addOption(self::OPTION_SYNC_CONFIGURATION, null, InputOption::VALUE_NONE, 'Sync preset configuration with database');
        $this->addOption(SynchronizeVariantConfigurationCommand::OPTION_SKIP_SETUP, null, InputOption::VALUE_NONE, sprintf('Skip setup - only applicable if \'--%s\' flag is set', self::OPTION_SYNC_CONFIGURATION));
        $this->addOption(self::OPTION_LIMIT, 'l', InputOption::VALUE_REQUIRED, 'Limit for how many images to process per resource. Default: unlimited');
        $this->addOption(self::OPTION_MAX_PENDING, 'm', InputOption::VALUE_REQUIRED, 'Limit for pending images. If there are more pending images than the provided value, no more images will be added. Limit is per resource. Default: unlimited');

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

        $maxPendingImages = $input->getOption(self::OPTION_MAX_PENDING);
        Assert::nullOrIntegerish($maxPendingImages);
        if ($maxPendingImages !== null) {
            $maxPendingImages = (int) $maxPendingImages;
            Assert::greaterThan($maxPendingImages, 0, 'The max pending limit must be greater than 0');
            $this->maxPendingImages = $maxPendingImages;
        }

        /** @var string[]|object $chosenResources */
        $chosenResources = $input->getArgument(self::ARGUMENT_RESOURCES);

        if (!is_array($chosenResources) || empty($chosenResources)) {
            $this->processableImageResources = $this->imageResourceRegistry->all();
        } else {
            foreach ($chosenResources as $resourceKey) {
                if ($this->imageResourceRegistry->has($resourceKey)) {
                    $this->processableImageResources[$resourceKey] = $this->imageResourceRegistry->get($resourceKey);
                } else {
                    $this->io->warning(sprintf('The requested resource \'%s\' is not available', $resourceKey));
                }
            }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->lock()) {
            $output->writeln('The command is already running in another process.');

            return 0;
        }

        try {
            if ($this->presetRegistry->isEmpty()) {
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

            $presetConfiguration = $this->presetConfigurationRepository->findNewest();
            if (null === $presetConfiguration) {
                $this->io->writeln('No preset configuration saved in the database. Run with --sync-configuration instead.');

                return 0;
            }

            $presets = $presetConfiguration->getPresets();

            $this->eventDispatcher->dispatch(new ProcessingStartedEvent($presets));

            foreach ($this->processableImageResources as $processableResource) {
                $this->processResource($processableResource, $presetConfiguration);
            }

            return 0;
        } finally {
            $this->release();
        }
    }

    private function processResource(ImageResource $resource, PresetConfigurationInterface $variantConfiguration): void
    {
        $this->io->section(sprintf('Processing resource: %s', $resource->class));

        $manager = $this->getManager($resource->class);

        /** @var ObjectRepository|EntityRepository $repository */
        $repository = $manager->getRepository($resource->class);
        Assert::isInstanceOf($repository, EntityRepository::class);

        if ($this->maxPendingImages !== null) {
            $pendingImages = $repository->count(['processingState' => ImageInterface::PROCESSING_STATE_PENDING]);
            if ($pendingImages >= $this->maxPendingImages) {
                $this->io->note(sprintf(
                    'There is already %d pending images which is more than the limit of %d. Not processing further.',
                    $pendingImages,
                    $this->maxPendingImages
                ));

                return;
            }
        }

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
        PresetConfigurationInterface $variantConfiguration,
        int $resultsPerPage = 50
    ): iterable {
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

        $resultCount = 0;
        $maxResults = $resultsPerPage;

        do {
            if ($this->limitPerResource !== null) {
                $remaining = $this->limitPerResource - $resultCount;
                $maxResults = min($maxResults, $remaining);
            }

            $qb->setMaxResults($maxResults);

            $images = $qb->getQuery()->getResult();
            Assert::isArray($images);

            /** @var ImageInterface $image */
            foreach ($images as $image) {
                yield $image;
            }

            $resultCount += count($images);

            $manager->clear();
        } while ([] !== $images && ($this->limitPerResource === null || $resultCount < $this->limitPerResource));
    }
}
