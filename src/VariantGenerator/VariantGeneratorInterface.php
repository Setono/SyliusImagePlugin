<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\VariantGenerator;

use Gaufrette\File;
use Setono\SyliusImagePlugin\Config\Variant;
use Setono\SyliusImagePlugin\File\ImageVariantFile;
use Setono\SyliusImagePlugin\Model\ImageInterface;

interface VariantGeneratorInterface
{
    public function getName(): string;

    /**
     * @param array<array-key, Variant> $variants
     *
     * @return iterable<ImageVariantFile>
     */
    public function generate(ImageInterface $image, File $file, array $variants): iterable;
}
