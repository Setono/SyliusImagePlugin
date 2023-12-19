<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Model;

use Setono\SyliusImagePlugin\Config\Preset;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TimestampableInterface;

interface PresetConfigurationInterface extends ResourceInterface, TimestampableInterface
{
    public function getId(): ?int;

    /**
     * @return array<string, Preset>
     */
    public function getPresets(): array;

    /**
     * @param array<string, Preset> $presets
     */
    public function setPresets(array $presets): void;
}
