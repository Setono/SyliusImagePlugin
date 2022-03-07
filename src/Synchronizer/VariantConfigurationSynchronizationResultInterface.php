<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Synchronizer;

use Setono\SyliusImagePlugin\VariantGenerator\SetupResultInterface;

interface VariantConfigurationSynchronizationResultInterface
{
    /** @return list<string> */
    public function getMessages(): array;

    public function hasMessages(): bool;

    /** @return list<SetupResultInterface> */
    public function getSetupResults(): array;
}
