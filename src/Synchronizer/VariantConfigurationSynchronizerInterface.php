<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Synchronizer;

use Setono\SyliusImagePlugin\ImageGenerator\ImageGeneratorInterface;

interface VariantConfigurationSynchronizerInterface
{
    /**
     * This will synchronize the variant configuration saved in the database
     * with the variant configuration given by the application configuration
     *
     * @param bool $runSetup If true, the `setup` method on the relevant VariantGenerators will be called
     *
     * @see ImageGeneratorInterface::setup();
     */
    public function synchronize(bool $runSetup = true): VariantConfigurationSynchronizationResultInterface;
}
