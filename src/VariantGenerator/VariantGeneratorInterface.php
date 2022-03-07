<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\VariantGenerator;

use Gaufrette\File;
use Setono\SyliusImagePlugin\Config\VariantCollectionInterface;
use Setono\SyliusImagePlugin\File\ImageVariantFile;
use Setono\SyliusImagePlugin\Model\ImageInterface;
use Setono\SyliusImagePlugin\Synchronizer\VariantConfigurationSynchronizerInterface;

interface VariantGeneratorInterface
{
    public function getName(): string;

    /**
     * @return iterable<ImageVariantFile>
     */
    public function generate(ImageInterface $image, File $file, VariantCollectionInterface $variantCollection): iterable;

    /**
     * This method may be called when the `VariantConfigurationSynchronizer` is run.
     *
     * Use this method to perform synchronization of vendor specific settings, if any.
     *
     * @see VariantConfigurationSynchronizerInterface::synchronize();
     */
    public function setup(VariantCollectionInterface $variantCollection): SetupResultInterface;
}
