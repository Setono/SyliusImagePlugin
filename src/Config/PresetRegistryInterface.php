<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Config;

interface PresetRegistryInterface
{
    /**
     * @return array<string, Preset>
     */
    public function all(): array;

    public function add(Preset $preset): void;

    /**
     * @throws \InvalidArgumentException if the preset does not exist
     */
    public function get(string $preset): Preset;

    /**
     * Returns true if the registry contains the given $preset
     *
     * @param string|Preset $preset
     */
    public function has($preset): bool;

    /**
     * Returns true if there are no registered presets
     */
    public function isEmpty(): bool;
}
