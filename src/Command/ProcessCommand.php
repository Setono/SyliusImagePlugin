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
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

final class ProcessCommand extends Command
{
    use ORMManagerTrait;

    protected static $defaultName = 'setono:sylius-image:process';

    /** @var string|null */
    protected static $defaultDescription = 'Processes all image variants';

    private MessageBusInterface $commandBus;

    private VariantCollectionInterface $variantCollection;

    private EventDispatcherInterface $eventDispatcher;

    private VariantConfigurationRepositoryInterface $variantConfigurationRepository;

    private FactoryInterface $variantConfigurationFactory;

    /** @var array<string, array{classes: array{model: string}}> */
    private array $resources;

    /**
     * @param array<string, array{classes: array{model: string}}> $resources
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        MessageBusInterface $commandBus,
        VariantCollectionInterface $variantCollection,
        EventDispatcherInterface $eventDispatcher,
        VariantConfigurationRepositoryInterface $variantConfigurationRepository,
        FactoryInterface $variantConfigurationFactory,
        array $resources
    ) {
        parent::__construct();

        $this->managerRegistry = $managerRegistry;
        $this->commandBus = $commandBus;
        $this->variantCollection = $variantCollection;
        $this->eventDispatcher = $eventDispatcher;
        $this->variantConfigurationRepository = $variantConfigurationRepository;
        $this->variantConfigurationFactory = $variantConfigurationFactory;
        $this->resources = $resources;
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

This will compare your plugin configuration with the newest database configuration and if there are changes a new
database configuration will be saved and consequently be used as the newest configuration. This also implies that if you
pass this flag and a new configuration is saved, all images will be processed. NOTE that this does NOT mean that
all variants are reprocessed, just the new variants when comparing the new configuration to the old configuration.

Example
-------

<comment>Old configuration</comment>

setono_sylius_image:
    filter_sets:
        sylius_shop_product_tiny_thumbnail: ~
        sylius_shop_product_small_thumbnail: ~
        sylius_shop_product_thumbnail: ~

<comment>New configuration</comment>

setono_sylius_image:
    filter_sets:
        sylius_shop_product_tiny_thumbnail: ~
        sylius_shop_product_small_thumbnail: ~
        sylius_shop_product_thumbnail: ~
        sylius_shop_product_large_thumbnail: ~

Here the <comment>sylius_shop_product_large_thumbnail</comment> will be processed for all images.
EOF
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->variantCollection->isEmpty()) {
            $output->writeln('No variants configured');

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
            $output->writeln(sprintf('No resources implements the interface %s', ImageInterface::class));

            return 0;
        }

        $this->eventDispatcher->dispatch(new ProcessingStartedEvent());

        $this->syncConfiguration($syncConfiguration);

        $variantConfiguration = $this->variantConfigurationRepository->findNewest();
        if (null === $variantConfiguration) {
            $output->writeln('No variant configuration saved in the database. Run with --sync-configuration instead.');

            return 0;
        }

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
        $manager = $this->getManager($class);

        /** @var ObjectRepository|EntityRepository $repository */
        $repository = $manager->getRepository($class);
        Assert::isInstanceOf($repository, EntityRepository::class);

        foreach ($this->getImages($repository, $manager, $variantConfiguration) as $image) {
            $this->commandBus->dispatch(ProcessImage::fromImage($image));
        }
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

        $qb = $repository->createQueryBuilder('o');
        $qb
            ->andWhere($qb->expr()->orX(
                'o.variantConfiguration is null',
                'o.variantConfiguration != :variantConfiguration',
            ))
            ->andWhere('o.processingState = :processingState')
            ->setParameter('variantConfiguration', $variantConfiguration)
            ->setParameter('processingState', ImageInterface::PROCESSING_STATE_PENDING)
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

    private function syncConfiguration(bool $syncConfiguration): void
    {
        if (!$syncConfiguration) {
            return;
        }

        $variantConfiguration = $this->variantConfigurationRepository->findNewest();
        if (null !== $variantConfiguration) {
            $variantCollection = $variantConfiguration->getVariantCollection();
            Assert::notNull($variantCollection);

            if ($variantCollection->equals($this->variantCollection)) {
                return;
            }
        }

        /** @var VariantConfigurationInterface|object $variantConfiguration */
        $variantConfiguration = $this->variantConfigurationFactory->createNew();
        Assert::isInstanceOf($variantConfiguration, VariantConfigurationInterface::class);

        $variantConfiguration->setVariantCollection($this->variantCollection);

        $manager = $this->getManager($variantConfiguration);
        $manager->persist($variantConfiguration);
        $manager->flush();
    }
}
