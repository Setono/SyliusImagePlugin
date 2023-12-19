<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Config;

final class PresetRegistry implements PresetRegistryInterface
{
    /**
     * An array of presets, indexed by name
     *
     * @var array<string, Preset>
     */
    private array $presets = [];

    /**
     * @param array<string, array{name: string, formats: list<string>, height?: int, width?: int, fit?: string, generator?: string}> $presets
     */
    public static function fromArray(array $presets): self
    {
        $obj = new self();

        foreach ($presets as $name => $preset) {
            $preset['name'] = $name;
            $obj->add(Preset::fromArray($preset));
        }

        return $obj;
    }

    public function all(): array
    {
        return $this->presets;
    }

    public function add(Preset $preset): void
    {
        if ($this->has($preset)) {
            return;
        }

        $this->presets[$preset->name] = $preset;
    }

    public function get(string $preset): Preset
    {
        if (!$this->has($preset)) {
            throw new \InvalidArgumentException(sprintf('The preset "%s" does not exist', $preset));
        }

        return $this->presets[$preset];
    }

    public function has($preset): bool
    {
        if ($preset instanceof Preset) {
            $preset = $preset->name;
        }

        return isset($this->presets[$preset]);
    }

    public function isEmpty(): bool
    {
        return [] === $this->presets;
    }
}
