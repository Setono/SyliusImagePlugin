<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Synchronizer;

use Symfony\Component\Console\Style\SymfonyStyle;

interface VariantConfigurationSynchronizationResultInterface
{
    // TODO: Should we just remove this and let implementors throw exceptions if they want execution halted?
    public function isStopExecution(): bool;

    public function reportResults(SymfonyStyle $io): void;
}
