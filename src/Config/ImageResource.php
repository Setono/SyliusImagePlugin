<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Config;

final class ImageResource
{
    /**
     * @readonly
     *
     * @var string The Sylius resource key, i.e. `sylius.product_image`
     */
    public string $resourceKey;

    /**
     * @readonly
     *
     * @var class-string The FQN class name of the class representing this resource
     */
    public string $className;

    /**
     * @readonly
     *
     * @var VariantCollectionInterface A collection of the variants that should be generated for this resource
     */
    public VariantCollectionInterface $variantCollection;

    /**
     * @param string $resourceKey The Sylius resource key, i.e. 'sylius.product_image'
     * @param class-string $className The FQN class name of the class representing this resource
     * @param VariantCollectionInterface $variantCollection A collection of the variants that should be generated for this resource
     */
    public function __construct(string $resourceKey, string $className, VariantCollectionInterface $variantCollection)
    {
        $this->resourceKey = $resourceKey;
        $this->className = $className;
        $this->variantCollection = $variantCollection;
    }
}
