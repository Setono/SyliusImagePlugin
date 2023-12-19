<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Config;

use Setono\SyliusImagePlugin\Model\ImageInterface;

final class ImageResourceRegistry implements ImageResourceRegistryInterface
{
    /** @var array<string, ImageResource> */
    private array $resources = [];

    /**
     * @param array<string, array{class: class-string<ImageInterface>, presets: list<Preset>}> $imageResources
     */
    public static function fromArray(array $imageResources): self
    {
        $obj = new self();

        foreach ($imageResources as $name => $imageResource) {
            $imageResource['name'] = $name;
            $obj->add(ImageResource::fromArray($imageResource));
        }

        return $obj;
    }

    public function add(ImageResource $resource): void
    {
        if ($this->has($resource)) {
            return;
        }

        $this->resources[$resource->name] = $resource;
    }

    public function all(): array
    {
        return $this->resources;
    }

    public function has($resource): bool
    {
        return $this->getName($resource) !== null;
    }

    public function get($resource): ImageResource
    {
        $name = $this->getName($resource);

        if ($name === null) {
            throw new \InvalidArgumentException('Resource does not exist');
        }

        return $this->resources[$name];
    }

    public function isEmpty(): bool
    {
        return [] === $this->resources;
    }

    /**
     * @param string|ImageInterface|ImageResource $resource
     */
    private function getName($resource): ?string
    {
        if ($resource instanceof ImageInterface) {
            foreach ($this->resources as $imageResource) {
                if ($imageResource->class === get_class($resource)) {
                    return $imageResource->name;
                }
            }

            return null;
        }
        if ($resource instanceof ImageResource) {
            return array_key_exists($resource->name, $this->resources) ? $resource->name : null;
        }

        foreach ($this->resources as $imageResource) {
            if ($imageResource->name === $resource || $imageResource->class === $resource) {
                return $imageResource->name;
            }
        }

        return null;
    }
}
