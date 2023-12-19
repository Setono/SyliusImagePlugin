<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\EventListener\Doctrine;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Gaufrette\FilesystemInterface;
use Setono\SyliusImagePlugin\File\ImageVariantFile;
use Setono\SyliusImagePlugin\Model\ImageInterface;
use Setono\SyliusImagePlugin\Repository\PresetConfigurationRepositoryInterface;

final class RemoveProcessedImagesListener
{
    private FilesystemInterface $processedImagesFilesystem;

    private PresetConfigurationRepositoryInterface $presetConfigurationRepository;

    public function __construct(
        FilesystemInterface $processedImagesFilesystem,
        PresetConfigurationRepositoryInterface $presetConfigurationRepository
    ) {
        $this->processedImagesFilesystem = $processedImagesFilesystem;
        $this->presetConfigurationRepository = $presetConfigurationRepository;
    }

    public function postRemove(LifecycleEventArgs $eventArgs): void
    {
        $obj = $eventArgs->getObject();
        if (!$obj instanceof ImageInterface) {
            return;
        }

        $path = $obj->getPath();
        if (null === $path) {
            return;
        }

        $presetConfiguration = $this->presetConfigurationRepository->findNewest();
        if (null === $presetConfiguration) {
            return;
        }

        $deletablePaths = [];

        foreach ($presetConfiguration->getPresets() as $item) {
            $deletablePaths = [sprintf('%s/%s', $item->name, $path)];
            foreach (ImageVariantFile::getNextGenerationFormatsAsExtensions() as $extension) {
                $deletablePaths[] = sprintf('%s/%s', $item->name, ImageVariantFile::replaceExtension($path, $extension));
            }
        }

        foreach ($deletablePaths as $deletablePath) {
            try {
                $this->processedImagesFilesystem->delete($deletablePath);
            } catch (\Throwable $e) {
                // Do not handle this exception because it just means that the file doesn't exist
            }
        }
    }
}
