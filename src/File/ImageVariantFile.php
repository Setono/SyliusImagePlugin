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

    /**
     * @return list<string>
     */
    public static function getNextGenerationFormatsAsExtensions(): array
    {
        return ['webp', 'avif'];
    }

    /**
     * @return list<string>
     */
    public static function getNextGenerationFormatsAsContentTypes(): array
    {
        return ['image/webp', 'image/avif'];
    }

    /**
     * Replaces the original extension with the value of $newExtension
     *
     * @param string $path i.e. ad/ef/sadfsadf.jpg
     * @param string $newExtension i.e. webp
     *
     * @return string i.e. ad/ef/sadfsadf.webp
     */
    public static function replaceExtension(string $path, string $newExtension): string
    {
        $pathInfo = pathinfo($path);

        return sprintf('%s/%s.%s', $pathInfo['dirname'], $pathInfo['filename'], $newExtension);
    }
}
