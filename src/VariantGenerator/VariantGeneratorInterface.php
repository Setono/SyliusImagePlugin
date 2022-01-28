<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\VariantGenerator;

use Gaufrette\File;
use Setono\SyliusImagePlugin\Config\Variant;
use Setono\SyliusImagePlugin\File\ImageVariantFile;

interface VariantGeneratorInterface
{
    public function getName(): string;

    /**
     * @param array<array-key, Variant> $variants
     * @param array<array-key, string> $formats
     *
     * @return iterable<ImageVariantFile>
     */
    public function generate(File $file, array $variants, array $formats = ['jpg', 'webp', 'avif']): iterable;
}
