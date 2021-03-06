<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Synchronizer;

use Doctrine\Persistence\ManagerRegistry;
use Setono\DoctrineObjectManagerTrait\ORM\ORMManagerTrait;
use Setono\SyliusImagePlugin\Config\Variant;
use Setono\SyliusImagePlugin\Config\VariantCollectionInterface;
use Setono\SyliusImagePlugin\Model\VariantConfigurationInterface;
use Setono\SyliusImagePlugin\Registry\VariantGeneratorRegistryInterface;
use Setono\SyliusImagePlugin\Repository\VariantConfigurationRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Webmozart\Assert\Assert;

final class VariantConfigurationSynchronizer implements VariantConfigurationSynchronizerInterface
{
    use ORMManagerTrait;

    private FactoryInterface $variantConfigurationFactory;

    private VariantConfigurationRepositoryInterface $variantConfigurationRepository;

    private VariantCollectionInterface $variantCollection;

    private VariantGeneratorRegistryInterface $generatorRegistry;

    public function __construct(
        FactoryInterface $variantConfigurationFactory,
        VariantConfigurationRepositoryInterface $variantConfigurationRepository,
        VariantCollectionInterface $variantCollection,
        ManagerRegistry $managerRegistry,
        VariantGeneratorRegistryInterface $generatorRegistry
    ) {
        $this->variantConfigurationFactory = $variantConfigurationFactory;
        $this->variantConfigurationRepository = $variantConfigurationRepository;
        $this->variantCollection = $variantCollection;
        $this->managerRegistry = $managerRegistry;
        $this->generatorRegistry = $generatorRegistry;
    }

    public function synchronize(bool $runSetup = true): VariantConfigurationSynchronizationResultInterface
    {
        $syncResult = new VariantConfigurationSynchronizationResult();

        if ($runSetup) {
            $generators = array_unique(array_map(static fn (Variant $variant) => $variant->generator, $this->variantCollection->toArray()));

            foreach ($generators as $generatorName) {
                $generator = $this->generatorRegistry->get($generatorName);
                $setupResult = $generator->setup($this->variantCollection);
                $syncResult->addSetupResult($setupResult);
            }
        }

        $variantConfiguration = $this->variantConfigurationRepository->findNewest();
        if (null !== $variantConfiguration) {
            $variantCollection = $variantConfiguration->getVariantCollection();
            Assert::notNull($variantCollection);

            if ($variantCollection->equals($this->variantCollection)) {
                $syncResult->addMessage('Variant configuration has not changed.');

                return $syncResult;
            }
        }

        /** @var VariantConfigurationInterface|object $variantConfiguration */
        $variantConfiguration = $this->variantConfigurationFactory->createNew();
        Assert::isInstanceOf($variantConfiguration, VariantConfigurationInterface::class);

        $variantConfiguration->setVariantCollection($this->variantCollection);

        $manager = $this->getManager($variantConfiguration);
        $manager->persist($variantConfiguration);
        $manager->flush();

        return $syncResult;
    }
}
