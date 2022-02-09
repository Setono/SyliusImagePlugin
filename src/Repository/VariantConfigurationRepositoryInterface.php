<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Repository;

use Setono\SyliusImagePlugin\Model\VariantConfigurationInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

interface VariantConfigurationRepositoryInterface extends RepositoryInterface
{
    public function findNewest(): ?VariantConfigurationInterface;
}
