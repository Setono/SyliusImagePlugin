<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Config;

use Setono\SyliusImagePlugin\Model\ImageInterface;

/**
 * @template-covariant TKey
 * @extends \Traversable<TKey, ImageResource>
 */
interface ImageResourceCollectionInterface extends \Traversable
{
    /**
     * @param string|ImageInterface|ImageResource $resource
     */
    public function has($resource): bool;

    /**
     * @param string|ImageInterface $resource
     */
    public function get($resource): ImageResource;
}
