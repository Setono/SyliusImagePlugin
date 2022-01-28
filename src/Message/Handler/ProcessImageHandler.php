<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Message\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Gaufrette\FilesystemInterface;
use Setono\DoctrineObjectManagerTrait\ORM\ORMManagerTrait;
use Setono\SyliusImagePlugin\Config\Variant;
use Setono\SyliusImagePlugin\Config\VariantCollectionInterface;
use Setono\SyliusImagePlugin\Message\Command\ProcessImage;
use Setono\SyliusImagePlugin\Model\ImageInterface;
use Setono\SyliusImagePlugin\VariantGenerator\VariantGeneratorInterface;
use Setono\SyliusImagePlugin\VariantGenerator\VariantGeneratorRegistryInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Webmozart\Assert\Assert;

final class ProcessImageHandler implements MessageHandlerInterface
{
    use ORMManagerTrait;

    private VariantGeneratorRegistryInterface $variantGeneratorRegistry;

    private VariantCollectionInterface $variantCollection;

    private FilesystemInterface $uploadedImagesFilesystem;

    private FilesystemInterface $processedImagesFilesystem;

    public function __construct(
        ManagerRegistry $managerRegistry,
        VariantGeneratorRegistryInterface $variantGeneratorRegistry,
        VariantCollectionInterface $variantCollection,
        FilesystemInterface $uploadedImagesFilesystem,
        FilesystemInterface $processedImagesFilesystem
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->variantGeneratorRegistry = $variantGeneratorRegistry;
        $this->variantCollection = $variantCollection;
        $this->uploadedImagesFilesystem = $uploadedImagesFilesystem;
        $this->processedImagesFilesystem = $processedImagesFilesystem;
    }

    public function __invoke(ProcessImage $message): void
    {
        $manager = $this->getManager($message->class);

        /** @var ImageInterface|object|null $image */
        $image = $manager->find($message->class, $message->imageId);
        if (null === $image) {
            return;
        }
        Assert::isInstanceOf($image, ImageInterface::class);

        /**
         * todo This is just a check - I am not sure this check should be here
         *
         * @var Variant $variant
         */
        foreach ($this->variantCollection as $variant) {
            if (!$this->variantGeneratorRegistry->has($variant->generator)) {
                throw new \RuntimeException(sprintf(
                    'The variant "%s" has defined its generator to be "%s", but no such generator has been registered.',
                    $variant->name,
                    $variant->generator
                ));
            }
        }

        $imageFile = $this->uploadedImagesFilesystem->get((string) $image->getPath());

        /** @var VariantGeneratorInterface $variantGenerator */
        foreach ($this->variantGeneratorRegistry as $variantGenerator) {
            $variants = $this->variantCollection->getByGenerator($variantGenerator);

            foreach ($variantGenerator->generate($imageFile, $variants) as $file) {
                $this->processedImagesFilesystem->write(sprintf(
                    '%s/%s',
                    $file->getVariant(),
                    self::replaceExtension((string) $image->getPath(), $file->getFormat())
                ), file_get_contents($file->getPathname()), true);

                @unlink($file->getPathname());
            }
        }

        $manager->flush();
    }

    private static function replaceExtension(string $path, string $newExtension): string
    {
        $pathInfo = pathinfo($path);

        return sprintf('%s/%s.%s', $pathInfo['dirname'], $pathInfo['filename'], $newExtension);
    }
}
