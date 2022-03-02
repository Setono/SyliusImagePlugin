<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Provider;

use Setono\SyliusImagePlugin\Model\ImageInterface;

final class ProcessableResourceProvider implements ProcessableResourceProviderInterface
{
    /** @var array<string, array{classes: array{model: class-string}}> */
    private array $resources;

    /**
     * @param array<string, array{classes: array{model: class-string}}> $resources
     */
    public function __construct(array $resources)
    {
        $this->resources = $resources;
    }

    public function getResources(): array
    {
        $processableResources = [];

        foreach ($this->resources as $resource) {
            if (!is_a($resource['classes']['model'], ImageInterface::class, true)) {
                continue;
            }

            $processableResources[] = $resource['classes']['model'];
        }

        return $processableResources;
    }
}
