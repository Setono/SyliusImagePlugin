<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Synchronizer;

use Setono\SyliusImagePlugin\VariantGenerator\SetupResultInterface;

interface VariantConfigurationSynchronizationResultInterface
{
    /** @return array<string, string> */
    public function getMessages(): array;

    /** @return array<string, SetupResultInterface> */
    public function getSetupResults(): array;
}
