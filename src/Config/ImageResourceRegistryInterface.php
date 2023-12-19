<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Config;

use Setono\SyliusImagePlugin\Model\ImageInterface;

interface ImageResourceRegistryInterface
{
    /**
     * Returns all the registered image resources, indexed by name
     *
     * @return array<string, ImageResource>
     */
    public function all(): array;

    /**
     * @param string|ImageInterface|ImageResource $resource Can be the sylius resource key, the FQN classname, an instance of ImageInterface or an ImageResource
     */
    public function has($resource): bool;

    /**
     * @param string|ImageInterface $resource Can be the sylius resource key, the FQN classname or an instance of ImageInterface
     */
    public function get($resource): ImageResource;

    /**
     * Returns true if there are no registered image resources
     */
    public function isEmpty(): bool;
}
