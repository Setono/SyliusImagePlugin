<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Synchronizer;

use Setono\SyliusImagePlugin\Config\VariantCollectionInterface;

interface VariantConfigurationSynchronizerInterface
{
    /**
     * This will synchronize the variant configuration saved in the database
     * with the variant configuration given by the application configuration
     *
     * @param bool $createVariantsIfNotExists If true, will create variants that are not yet available at the generator
     */
    public function synchronize(bool $createVariantsIfNotExists): VariantCollectionInterface;
}
