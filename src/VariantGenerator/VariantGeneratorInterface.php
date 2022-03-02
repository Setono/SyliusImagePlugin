<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\VariantGenerator;

use Gaufrette\File;
use Setono\SyliusImagePlugin\Config\VariantCollectionInterface;
use Setono\SyliusImagePlugin\File\ImageVariantFile;
use Setono\SyliusImagePlugin\Model\ImageInterface;

interface VariantGeneratorInterface
{
    public function getName(): string;

    /**
     * @return iterable<ImageVariantFile>
     */
    public function generate(ImageInterface $image, File $file, VariantCollectionInterface $variantCollection): iterable;
}
