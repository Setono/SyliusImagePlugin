<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Synchronizer;

use Doctrine\Persistence\ManagerRegistry;
use Setono\DoctrineObjectManagerTrait\ORM\ORMManagerTrait;
use Setono\SyliusImagePlugin\Config\VariantCollectionInterface;
use Setono\SyliusImagePlugin\Model\VariantConfigurationInterface;
use Setono\SyliusImagePlugin\Repository\VariantConfigurationRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Webmozart\Assert\Assert;

final class VariantConfigurationSynchronizer implements VariantConfigurationSynchronizerInterface
{
    use ORMManagerTrait;

    private FactoryInterface $variantConfigurationFactory;

    private VariantConfigurationRepositoryInterface $variantConfigurationRepository;

    private VariantCollectionInterface $variantCollection;

    public function __construct(
        FactoryInterface $variantConfigurationFactory,
        VariantConfigurationRepositoryInterface $variantConfigurationRepository,
        VariantCollectionInterface $variantCollection,
        ManagerRegistry $managerRegistry
    ) {
        $this->variantConfigurationFactory = $variantConfigurationFactory;
        $this->variantConfigurationRepository = $variantConfigurationRepository;
        $this->variantCollection = $variantCollection;
        $this->managerRegistry = $managerRegistry;
    }

    public function synchronize(): void
    {
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
