<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Config;

use Webmozart\Assert\Assert;

final class VariantCollectionFactory
{
    /** @var array[] */
    private array $filterSets;

    /**
     * These are the LiipImagineBundle configured filter sets
     *
     * @param array<string, array> $filterSets
     */
    public function __construct(array $filterSets)
    {
        $this->filterSets = $filterSets;
    }

    /**
     * This is the configuration from this plugin
     *
     * @param array<string, array{generator: string}> $configurationValues
     */
    public function createFromConfiguration(array $configurationValues): VariantCollectionInterface
    {
        $variantCollection = new VariantCollection();

        foreach ($configurationValues as $filterSet => $configurationValue) {
            Assert::keyExists($this->filterSets, $filterSet, sprintf(
                'The filter set "%s" is not configured in the LiipImagineBundle',
                $filterSet
            ));
            $variantCollection->add(
                Variant::fromFilterSet($filterSet, $configurationValue['generator'], $this->filterSets[$filterSet])
            );
        }

        return $variantCollection;
    }
}
