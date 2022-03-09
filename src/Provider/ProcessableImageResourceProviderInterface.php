<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Provider;

use Setono\SyliusImagePlugin\Config\ImageResource;

interface ProcessableImageResourceProviderInterface
{
    /**
     * Will return an array of ImageResources that are processable
     *
     * @return list<ImageResource>
     */
    public function getResources(): array;
}
