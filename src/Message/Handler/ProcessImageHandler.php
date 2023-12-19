<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Message\Handler;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\ManagerRegistry;
use Gaufrette\FilesystemInterface;
use Setono\DoctrineObjectManagerTrait\ORM\ORMManagerTrait;
use Setono\SyliusImagePlugin\Config\ImageResourceRegistryInterface;
use Setono\SyliusImagePlugin\Config\Preset;
use Setono\SyliusImagePlugin\Exception\ImageProcessingFailedException;
use Setono\SyliusImagePlugin\File\ImageVariantFile;
use Setono\SyliusImagePlugin\ImageGenerator\ImageGeneratorRegistryInterface;
use Setono\SyliusImagePlugin\Message\Command\ProcessImage;
use Setono\SyliusImagePlugin\Model\ImageInterface;
use Setono\SyliusImagePlugin\Repository\PresetConfigurationRepositoryInterface;
use Setono\SyliusImagePlugin\Workflow\ProcessWorkflow;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Workflow\Registry;
use Webmozart\Assert\Assert;

final class ProcessImageHandler implements MessageHandlerInterface
{
    use ORMManagerTrait;

    private ImageGeneratorRegistryInterface $imageGeneratorRegistry;

    private PresetConfigurationRepositoryInterface $presetConfigurationRepository;

    private Registry $workflowRegistry;

    private FilesystemInterface $uploadedImagesFilesystem;

    private FilesystemInterface $processedImagesFilesystem;

    private ImageResourceRegistryInterface $imageResourceRegistry;

    public function __construct(
        ManagerRegistry $managerRegistry,
        ImageGeneratorRegistryInterface $imageGeneratorRegistry,
        PresetConfigurationRepositoryInterface $presetConfigurationRepository,
        ImageResourceRegistryInterface $imageResourceRegistry,
        Registry $workflowRegistry,
        FilesystemInterface $uploadedImagesFilesystem,
        FilesystemInterface $processedImagesFilesystem
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->imageGeneratorRegistry = $imageGeneratorRegistry;
        $this->presetConfigurationRepository = $presetConfigurationRepository;
        $this->workflowRegistry = $workflowRegistry;
        $this->uploadedImagesFilesystem = $uploadedImagesFilesystem;
        $this->processedImagesFilesystem = $processedImagesFilesystem;
        $this->imageResourceRegistry = $imageResourceRegistry;
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

        $presetConfiguration = $this->presetConfigurationRepository->findNewest();
        if (null === $presetConfiguration) {
            // todo why don't we just create one then?
            throw new UnrecoverableMessageHandlingException('No variant configuration saved in the database');
        }

        /**
         * Here we check that we can actually start processing the image and by flushing we also utilize Doctrines
         * optimistic locking feature. See: https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/transactions-and-concurrency.html#optimistic-locking
         */
        $workflow = $this->workflowRegistry->get($image, ProcessWorkflow::NAME);
        if (!$workflow->can($image, ProcessWorkflow::TRANSITION_START)) {
            return;
        }

        $workflow->apply($image, ProcessWorkflow::TRANSITION_START);

        try {
            $manager->flush();
        } catch (OptimisticLockException $e) {
            throw ImageProcessingFailedException::fromCommand($message, $e);
        }

        try {
            $imageFile = $this->uploadedImagesFilesystem->get((string) $image->getPath());

            $imageResource = $this->imageResourceRegistry->get($image);

            foreach ($this->imageGeneratorRegistry->all() as $imageGenerator) {
                $presets = [];
                foreach ($imageResource->presets as $preset) {
                    $tmpPreset = clone $preset;
                    $tmpPreset->formats = [];

                    foreach ($preset->formats as $format) {
                        if($imageGenerator->supportsFormat($format)) {
                            $tmpPreset->formats[] = $format;
                        }
                    }

                    $presets[] = $tmpPreset;
                }
                foreach ($imageGenerator->generate($image, $imageFile, $presets) as $file) {
                    $this->processedImagesFilesystem->write(sprintf(
                        '%s/%s',
                        $file->getVariant(),
                        ImageVariantFile::replaceExtension((string) $image->getPath(), $file->getFileType())
                    ), file_get_contents($file->getPathname()), true);

                    @unlink($file->getPathname());
                }
            }

            $image->setPresetConfiguration($presetConfiguration);

            $workflow->apply($image, ProcessWorkflow::TRANSITION_FINISH);
            $manager->flush();
        } catch (\Throwable $e) {
            $workflow->apply($image, ProcessWorkflow::TRANSITION_FAIL);
            $manager->flush();

            throw ImageProcessingFailedException::fromCommand($message, $e);
        }
    }
}
