<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Provider;

interface ProcessableResourceProviderInterface
{
    /**
     * Will return an array of resource class strings that are able to be processed
     *
     * @return list<class-string>
     */
    public function getResources(): array;
}
