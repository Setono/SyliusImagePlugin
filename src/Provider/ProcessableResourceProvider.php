<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Provider;

use Setono\SyliusImagePlugin\Config\ProcessableResourceCollectionInterface;

final class ProcessableResourceProvider implements ProcessableResourceProviderInterface
{
    private ProcessableResourceCollectionInterface $processableResourceCollection;

    public function __construct(ProcessableResourceCollectionInterface $processableResourceCollection)
    {
        $this->processableResourceCollection = $processableResourceCollection;
    }

    public function getResources(): array
    {
        $processableResources = [];

        foreach ($this->processableResourceCollection as $processableResource) {
            $processableResources[] = $processableResource;
        }

        return $processableResources;
    }
}
