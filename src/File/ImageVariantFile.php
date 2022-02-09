<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\File;

final class ImageVariantFile extends \SplFileInfo
{
    private string $variant;

    private string $fileType;

    public function __construct(string $filename, string $fileType, string $variant)
    {
        parent::__construct($filename);

        $this->variant = $variant;
        $this->fileType = $fileType;
    }

    public function getVariant(): string
    {
        return $this->variant;
    }

    public function getFileType(): string
    {
        return $this->fileType;
    }
}
