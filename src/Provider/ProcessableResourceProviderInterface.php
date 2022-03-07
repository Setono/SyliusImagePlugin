<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Provider;

use Setono\SyliusImagePlugin\Config\ProcessableResource;

interface ProcessableResourceProviderInterface
{
    /**
     * Will return an array of resource class strings that are able to be processed
     *
     * @return list<ProcessableResource>
     */
    public function getResources(): array;
}
