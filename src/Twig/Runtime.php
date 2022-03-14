<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Twig;

use Liip\ImagineBundle\Templating\FilterExtension;
use Setono\SyliusImagePlugin\Resolver\ProcessedVariantPathResolverInterface;
use Twig\Extension\RuntimeExtensionInterface;

final class Runtime implements RuntimeExtensionInterface
{
    private ProcessedVariantPathResolverInterface $processedVariantPathResolver;

    private FilterExtension $filterExtension;

    public function __construct(
        ProcessedVariantPathResolverInterface $processedVariantPathResolver,
        FilterExtension $filterExtension
    ) {
        $this->filterExtension = $filterExtension;
        $this->processedVariantPathResolver = $processedVariantPathResolver;
    }

    public function imagePath(string $relativePath, string $variant): string // todo return value object instead?
    {
        $processedVariantPath = $this->processedVariantPathResolver->getPublicVariantPath($relativePath, $variant);

        return $processedVariantPath ?? $this->filterExtension->filter($relativePath, $variant); // todo add other arguments
    }
}
