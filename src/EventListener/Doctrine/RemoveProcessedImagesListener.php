<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\EventListener\Doctrine;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Gaufrette\FilesystemInterface;
use Setono\SyliusImagePlugin\Config\Variant;
use Setono\SyliusImagePlugin\File\ImageVariantFile;
use Setono\SyliusImagePlugin\Model\ImageInterface;
use Setono\SyliusImagePlugin\Repository\VariantConfigurationRepositoryInterface;
use Webmozart\Assert\Assert;

final class RemoveProcessedImagesListener
{
    private FilesystemInterface $processedImagesFilesystem;

    private VariantConfigurationRepositoryInterface $variantConfigurationRepository;

    public function __construct(
        FilesystemInterface $processedImagesFilesystem,
        VariantConfigurationRepositoryInterface $variantConfigurationRepository
    ) {
        $this->processedImagesFilesystem = $processedImagesFilesystem;
        $this->variantConfigurationRepository = $variantConfigurationRepository;
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

        $variantConfiguration = $this->variantConfigurationRepository->findNewest();
        if (null === $variantConfiguration) {
            return;
        }

        $variantCollection = $variantConfiguration->getVariantCollection();
        Assert::notNull($variantCollection);

        $deletablePaths = [];

        /** @var Variant $item */
        foreach ($variantCollection as $item) {
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
