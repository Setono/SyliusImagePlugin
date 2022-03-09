<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Provider;

use Setono\SyliusImagePlugin\Config\ImageResourceCollectionInterface;

final class ProcessableImageResourceProvider implements ProcessableImageResourceProviderInterface
{
    private ImageResourceCollectionInterface $imageResourceCollection;

    public function __construct(ImageResourceCollectionInterface $processableResourceCollection)
    {
        $this->imageResourceCollection = $processableResourceCollection;
    }

    public function getResources(): array
    {
        $processableResources = [];

        foreach ($this->imageResourceCollection as $imageResource) {
            $processableResources[] = $imageResource;
        }

        return $processableResources;
    }
}
