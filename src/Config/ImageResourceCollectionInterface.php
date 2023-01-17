<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Config;

use Setono\SyliusImagePlugin\Model\ImageInterface;

/**
 * @extends \Traversable<array-key, ImageResource>
 */
interface ImageResourceCollectionInterface extends \Traversable
{
    /**
     * @param string|ImageInterface|ImageResource $resource Can be the sylius resource key, the FQN classname, an instance of ImageInterface or an ImageResource
     */
    public function has($resource): bool;

    /**
     * @param string|ImageInterface $resource Can be the sylius resource key, the FQN classname or an instance of ImageInterface
     */
    public function get($resource): ImageResource;
}
