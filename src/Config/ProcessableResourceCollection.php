<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Config;

use Setono\SyliusImagePlugin\Model\ImageInterface;

final class ProcessableResourceCollection implements ProcessableResourceCollectionInterface, \IteratorAggregate
{
    /** @var array<string, ProcessableResource> */
    private array $resources = [];

    /**
     * @param string|ImageInterface|ProcessableResource $resource
     */
    private function getResourceKey($resource): ?string
    {
        if ($resource instanceof ImageInterface) {
            foreach ($this->resources as $processableResource) {
                if ($processableResource->getClassName() === get_class($resource)) {
                    return $processableResource->getResource();
                }
            }

            return null;
        }
        if ($resource instanceof ProcessableResource) {
            return array_key_exists($resource->getResource(), $this->resources) ? $resource->getResource() : null;
        }

        return array_key_exists($resource, $this->resources) ? $resource : null;
    }

    public function add(ProcessableResource $processableResource): void
    {
        if ($this->has($processableResource)) {
            return;
        }

        $this->resources[$processableResource->getResource()] = $processableResource;
    }

    public function has($resource): bool
    {
        return $this->getResourceKey($resource) !== null;
    }

    public function get($resource): ProcessableResource
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
