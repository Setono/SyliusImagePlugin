<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\File;

final class ImageVariantFile extends \SplFileInfo
{
    private string $variant;

    private string $format;

    public function __construct(string $filename, string $variant, string $format)
    {
        parent::__construct($filename);

        $this->variant = $variant;
        $this->format = $format;
    }

    public function getVariant(): string
    {
        return $this->variant;
    }

    public function getFormat(): string
    {
        return $this->format;
    }
}
