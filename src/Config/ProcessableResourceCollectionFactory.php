<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Config;

use Setono\SyliusImagePlugin\Model\ImageInterface;
use Webmozart\Assert\Assert;

final class ProcessableResourceCollectionFactory
{
    private VariantCollectionInterface $variantCollection;

    private array $resources;

    public function __construct(VariantCollectionInterface $variantCollection, array $resources)
    {
        $this->variantCollection = $variantCollection;
        $this->resources = $resources;
    }

    /**
     * @param array<string, array{variants: array<array-key, string>}> $configurationValue
     */
    public function createFromConfiguration(array $configurationValue): ProcessableResourceCollectionInterface
    {
        $resourceCollection = new ProcessableResourceCollection();

        if (empty($configurationValue)) {
            $allVariants = array_keys($this->variantCollection->toArray());
            foreach ($this->resources as $syliusResourceName => $syliusResource) {
                if (is_a($syliusResource['classes']['model'], ImageInterface::class, true)) {
                    $configurationValue[$syliusResourceName] = [
                        'variants' => $allVariants,
                    ];
                }
            }
        }

        foreach ($configurationValue as $resourceName => $resource) {
            Assert::keyExists($this->resources, $resourceName, sprintf(
                'The resource "%s" was not found in Sylius resources',
                $resourceName
            ));

            $syliusResource = $this->resources[$resourceName];
            $model = $syliusResource['classes']['model'];
            Assert::isAOf($model, ImageInterface::class, sprintf(
                'The resource "%s" (model: %s) does not implement %s',
                $resourceName,
                $model,
                ImageInterface::class
            ));

            $variantCollection = new VariantCollection();

            if (empty($resource['variants'])) {
                foreach ($this->variantCollection as $variant) {
                    $variantCollection->add($variant);
                }
            } else {
                foreach ($resource['variants'] as $variant) {
                    $variantCollection->add($this->variantCollection->get($variant));
                }
            }

            Assert::string($model);
            $resourceCollection->add(new ProcessableResource($resourceName, $model, $variantCollection));
        }

        return $resourceCollection;
    }
}
