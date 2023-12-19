<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Model;

use Setono\SyliusImagePlugin\Config\Preset;
use Sylius\Component\Resource\Model\TimestampableTrait;

class PresetConfiguration implements PresetConfigurationInterface
{
    use TimestampableTrait;

    protected ?int $id = null;

    /** @var array<string, Preset> */
    protected array $presets = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPresets(): array
    {
        return $this->presets;
    }

    public function setPresets(array $presets): void
    {
        $this->presets = $presets;
    }
}
