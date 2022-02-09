<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Model;

use DateTimeInterface;
use Sylius\Component\Core\Model\ImageInterface as BaseImageInterface;

interface ImageInterface extends BaseImageInterface
{
    public const PROCESSING_STATE_PENDING = 'pending';

    public const PROCESSING_STATE_PROCESSING = 'processing';

    public const PROCESSING_STATE_PROCESSED = 'processed';

    public const PROCESSING_STATE_FAILED = 'failed';

    public function getProcessingState(): string;

    public function setProcessingState(string $processingState): void;

    public function getProcessingStateUpdatedAt(): ?DateTimeInterface;

    public function getProcessingTries(): int;

    public function incrementProcessingTries(int $increment = 1): void;

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
