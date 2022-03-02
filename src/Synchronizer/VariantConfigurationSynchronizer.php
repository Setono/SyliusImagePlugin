<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Synchronizer;

use Doctrine\Persistence\ManagerRegistry;
use Setono\DoctrineObjectManagerTrait\ORM\ORMManagerTrait;
use Setono\SyliusImagePlugin\Client\Cloudflare\ClientInterface;
use Setono\SyliusImagePlugin\Client\Cloudflare\Response\VariantResult;
use Setono\SyliusImagePlugin\Config\VariantCollectionInterface;
use Setono\SyliusImagePlugin\Model\VariantConfigurationInterface;
use Setono\SyliusImagePlugin\Repository\VariantConfigurationRepositoryInterface;
use Setono\SyliusImagePlugin\VariantGenerator\CloudflareVariantGenerator;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\DependencyInjection\Container;
use Webmozart\Assert\Assert;

final class VariantConfigurationSynchronizer implements VariantConfigurationSynchronizerInterface
{
    use ORMManagerTrait;

    private FactoryInterface $variantConfigurationFactory;

    private VariantConfigurationRepositoryInterface $variantConfigurationRepository;

    private VariantCollectionInterface $variantCollection;

    private ClientInterface $client;

    public function __construct(
        FactoryInterface $variantConfigurationFactory,
        VariantConfigurationRepositoryInterface $variantConfigurationRepository,
        VariantCollectionInterface $variantCollection,
        ManagerRegistry $managerRegistry,
        ClientInterface $client
    ) {
        $this->variantConfigurationFactory = $variantConfigurationFactory;
        $this->variantConfigurationRepository = $variantConfigurationRepository;
        $this->variantCollection = $variantCollection;
        $this->managerRegistry = $managerRegistry;
        $this->client = $client;
    }

    public function synchronize(bool $createVariantsIfNotExists): VariantCollectionInterface
    {
        $variantConfiguration = $this->variantConfigurationRepository->findNewest();
        if (null !== $variantConfiguration) {
            $variantCollection = $variantConfiguration->getVariantCollection();
            Assert::notNull($variantCollection);

            if ($variantCollection->equals($this->variantCollection)) {
                return $variantCollection;
            }
        }

        $this->checkAvailability($this->variantCollection);

        if ($createVariantsIfNotExists) {
            $this->createVariants($this->variantCollection);
        }

        /** @var VariantConfigurationInterface|object $variantConfiguration */
        $variantConfiguration = $this->variantConfigurationFactory->createNew();
        Assert::isInstanceOf($variantConfiguration, VariantConfigurationInterface::class);

        $variantConfiguration->setVariantCollection($this->variantCollection);

        $manager = $this->getManager($variantConfiguration);
        $manager->persist($variantConfiguration);
        $manager->flush();

        return $this->variantCollection;
    }

    private function checkAvailability(VariantCollectionInterface $variantCollection): void
    {
        if (!$variantCollection->hasOneWithGenerator(CloudflareVariantGenerator::NAME)) {
            return;
        }

        $existingVariants = array_map(static function (VariantResult $variantResult) {
            return Container::underscore($variantResult->id); // variants in Cloudflare are saved as camel case
        }, $this->client->getVariants()->result->variants);

        foreach ($variantCollection->getByGenerator(CloudflareVariantGenerator::NAME) as $variant) {
            if (in_array($variant->name, $existingVariants, true)) {
                $variant->setAvailable(true);
            }
        }
    }

    private function createVariants(VariantCollectionInterface $variantCollection): void
    {
        $variants = $variantCollection->getByGenerator(CloudflareVariantGenerator::NAME);

        foreach ($variants as $variant) {
            if ($variant->isAvailable() || !$variant->isCreatable()) {
                continue;
            }

            $this->client->createVariant($variant->name, [
                'fit' => $variant->fit,
                'width' => $variant->width,
                'height' => $variant->height,
            ]);
        }
    }
}
