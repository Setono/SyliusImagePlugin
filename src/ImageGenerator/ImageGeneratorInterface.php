<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\ImageGenerator;

use Gaufrette\File;
use Setono\SyliusImagePlugin\Config\Preset;
use Setono\SyliusImagePlugin\File\ImageVariantFile;
use Setono\SyliusImagePlugin\Model\ImageInterface;
use Setono\SyliusImagePlugin\Synchronizer\VariantConfigurationSynchronizerInterface;

interface ImageGeneratorInterface
{
    /**
     * @param list<Preset> $presets
     *
     * @return iterable<ImageVariantFile>
     */
    public function generate(ImageInterface $image, File $file, array $presets): iterable;

    /**
     * Returns true if this image generators supports the given format
     */
    public function supportsFormat(string $format): bool;

    /**
     * This method may be called when the `VariantConfigurationSynchronizer` is run.
     *
     * Use this method to perform synchronization of vendor specific settings, if any.
     *
     * @see VariantConfigurationSynchronizerInterface::synchronize();
     *
     * @param list<Preset> $presets
     */
    public function setup(array $presets): SetupResultInterface;
}
