<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Repository;

use Setono\SyliusImagePlugin\Model\PresetConfigurationInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

interface PresetConfigurationRepositoryInterface extends RepositoryInterface
{
    public function findNewest(): ?PresetConfigurationInterface;
}
