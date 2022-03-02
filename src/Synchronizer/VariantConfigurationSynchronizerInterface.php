<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Synchronizer;

interface VariantConfigurationSynchronizerInterface
{
    /**
     * This will synchronize the variant configuration saved in the database
     * with the variant configuration given by the application configuration
     */
    public function synchronize(): void;
}
