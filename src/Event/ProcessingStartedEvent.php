<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Event;

use Setono\SyliusImagePlugin\Config\Preset;

final class ProcessingStartedEvent
{
    /**
     * @readonly
     *
     * @var array<string, Preset>
     */
    public array $presets;

    /**
     * @param array<string, Preset> $presets
     */
    public function __construct(array $presets)
    {
        $this->presets = $presets;
    }
}
