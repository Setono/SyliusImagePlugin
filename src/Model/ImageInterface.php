<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Model;

use Sylius\Component\Core\Model\ImageInterface as BaseImageInterface;

interface ImageInterface extends BaseImageInterface
{
    public function getVariantConfiguration(): ?VariantConfigurationInterface;

    public function setVariantConfiguration(VariantConfigurationInterface $variantConfiguration): void;

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array;

    /**
     * @param array<string, mixed> $metadata
     */
    public function setMetadata(array $metadata): void;

    /**
     * @param mixed $value
     */
    public function setMetadataEntry(string $key, $value): void;

    /**
     * Returns null if the key doesn't exist
     *
     * @return mixed|null
     */
    public function getMetadataEntry(string $key);
}
