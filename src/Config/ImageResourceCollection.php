<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Config;

use Setono\SyliusImagePlugin\Model\ImageInterface;

final class ImageResourceCollection implements ImageResourceCollectionInterface, \IteratorAggregate
{
    /** @var array<string, ImageResource> */
    private array $resources = [];

    /**
     * @param string|ImageInterface|ImageResource $resource
     */
    private function getResourceKey($resource): ?string
    {
        if ($resource instanceof ImageInterface) {
            foreach ($this->resources as $imageResource) {
                if ($imageResource->className === get_class($resource)) {
                    return $imageResource->resourceKey;
                }
            }

            return null;
        }
        if ($resource instanceof ImageResource) {
            return array_key_exists($resource->resourceKey, $this->resources) ? $resource->resourceKey : null;
        }

        foreach ($this->resources as $imageResource) {
            if ($imageResource->resourceKey === $resource || $imageResource->className === $resource) {
                return $imageResource->resourceKey;
            }
        }

        return null;
    }

    public function add(ImageResource $imageResource): void
    {
        if ($this->has($imageResource)) {
            return;
        }

        $this->resources[$imageResource->resourceKey] = $imageResource;
    }

    public function has($resource): bool
    {
        return $this->getResourceKey($resource) !== null;
    }

    public function get($resource): ImageResource
    {
        $resourceKey = $this->getResourceKey($resource);

        if ($resourceKey === null) {
            throw new \InvalidArgumentException('Resource does not exist');
        }

        return $this->resources[$resourceKey];
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->resources);
    }
}
