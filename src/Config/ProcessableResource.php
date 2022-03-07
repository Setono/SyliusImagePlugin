<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Config;

final class ProcessableResource
{
    private string $resource;

    /** @var class-string */
    private string $className;

    private VariantCollectionInterface $variantCollection;

    /**
     * @param class-string $className
     */
    public function __construct(string $resource, string $className, VariantCollectionInterface $variantCollection)
    {
        $this->resource = $resource;
        $this->className = $className;
        $this->variantCollection = $variantCollection;
    }

    public function getResource(): string
    {
        return $this->resource;
    }

    /**
     * @return class-string
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    public function getVariantCollection(): VariantCollectionInterface
    {
        return $this->variantCollection;
    }
}
