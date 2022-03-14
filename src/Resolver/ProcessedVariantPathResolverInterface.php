<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Resolver;

use Setono\SyliusImagePlugin\Model\ImageInterface;

interface ProcessedVariantPathResolverInterface
{
    /**
     * @param string $relativePath The relative path to the image (`ImageInterface::getPath()`)
     * @param string $variant The variant to obtain a path for
     *
     * @return string|null If the processed variant for the image is found, returns a "public path" to it.
     * Returns null if a processed variant is not found. See configuration for `setono_sylius_image.public_processed_path`
     * for definition of public.
     *
     * @see ImageInterface::getPath()
     */
    public function getPublicVariantPath(string $relativePath, string $variant): ?string;
}
