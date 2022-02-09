<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Model;

use Setono\SyliusImagePlugin\Config\VariantCollectionInterface;
use Sylius\Component\Resource\Model\TimestampableTrait;

class VariantConfiguration implements VariantConfigurationInterface
{
    use TimestampableTrait;

    protected ?int $id = null;

    protected ?VariantCollectionInterface $variantCollection = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVariantCollection(): ?VariantCollectionInterface
    {
        return $this->variantCollection;
    }

    public function setVariantCollection(VariantCollectionInterface $variantCollection): void
    {
        $this->variantCollection = $variantCollection;
    }
}
