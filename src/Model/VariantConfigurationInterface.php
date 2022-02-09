<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Model;

use Setono\SyliusImagePlugin\Config\VariantCollectionInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TimestampableInterface;

interface VariantConfigurationInterface extends ResourceInterface, TimestampableInterface
{
    public function getId(): ?int;

    public function getVariantCollection(): ?VariantCollectionInterface;

    public function setVariantCollection(VariantCollectionInterface $variantCollection): void;
}
