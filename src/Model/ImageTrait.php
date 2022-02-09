<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Model;

use Doctrine\ORM\Mapping as ORM;

trait ImageTrait
{
    /**
     * @ORM\Column(type="integer", options={"default": 1})
     * @ORM\Version
     */
    protected int $version = 1;

    /** @ORM\Column(type="string", options={"default": ImageInterface::PROCESSING_STATE_PENDING}) */
    protected string $processingState = ImageInterface::PROCESSING_STATE_PENDING;

    /**
     * @ORM\ManyToOne(targetEntity="Setono\SyliusImagePlugin\Model\VariantConfigurationInterface")
     * @ORM\JoinColumn(name="variant_configuration", referencedColumnName="id")
     */
    protected ?VariantConfigurationInterface $variantConfiguration = null;

    /**
     * @ORM\Column(type="array", nullable=true)
     *
     * @var array<string, mixed>
     */
    protected ?array $metadata = [];

    public function getProcessingState(): string
    {
        return $this->processingState;
    }

    public function setProcessingState(string $processingState): void
    {
        $this->processingState = $processingState;
    }

    public function getVariantConfiguration(): ?VariantConfigurationInterface
    {
        return $this->variantConfiguration;
    }

    public function setVariantConfiguration(?VariantConfigurationInterface $variantConfiguration): void
    {
        $this->variantConfiguration = $variantConfiguration;
    }

    public function getMetadata(): array
    {
        return $this->metadata ?? [];
    }

    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function setMetadataEntry(string $key, $value): void
    {
        if (null === $this->metadata) {
            $this->metadata = [];
        }

        $this->metadata[$key] = $value;
    }

    public function getMetadataEntry(string $key)
    {
        return $this->metadata[$key] ?? null;
    }
}
