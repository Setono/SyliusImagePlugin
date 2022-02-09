<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Model;

use Doctrine\ORM\Mapping as ORM;

trait ImageTrait
{
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
