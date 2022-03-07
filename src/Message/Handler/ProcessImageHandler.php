<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Message\Handler;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\ManagerRegistry;
use Gaufrette\FilesystemInterface;
use Setono\DoctrineObjectManagerTrait\ORM\ORMManagerTrait;
use Setono\SyliusImagePlugin\Config\ProcessableResourceCollectionInterface;
use Setono\SyliusImagePlugin\Config\Variant;
use Setono\SyliusImagePlugin\Exception\ImageProcessingFailedException;
use Setono\SyliusImagePlugin\File\ImageVariantFile;
use Setono\SyliusImagePlugin\Message\Command\ProcessImage;
use Setono\SyliusImagePlugin\Model\ImageInterface;
use Setono\SyliusImagePlugin\Repository\VariantConfigurationRepositoryInterface;
use Setono\SyliusImagePlugin\VariantGenerator\VariantGeneratorInterface;
use Setono\SyliusImagePlugin\VariantGenerator\VariantGeneratorRegistryInterface;
use Setono\SyliusImagePlugin\Workflow\ProcessWorkflow;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Workflow\Registry;
use Webmozart\Assert\Assert;

final class ProcessImageHandler implements MessageHandlerInterface
{
    use ORMManagerTrait;

    private VariantGeneratorRegistryInterface $variantGeneratorRegistry;

    private VariantConfigurationRepositoryInterface $variantConfigurationRepository;

    private Registry $workflowRegistry;

    private FilesystemInterface $uploadedImagesFilesystem;

    private FilesystemInterface $processedImagesFilesystem;

    private ProcessableResourceCollectionInterface $processableResourceCollection;

    public function __construct(
        ManagerRegistry $managerRegistry,
        VariantGeneratorRegistryInterface $variantGeneratorRegistry,
        VariantConfigurationRepositoryInterface $variantConfigurationRepository,
        ProcessableResourceCollectionInterface $processableResourceCollection,
        Registry $workflowRegistry,
        FilesystemInterface $uploadedImagesFilesystem,
        FilesystemInterface $processedImagesFilesystem
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->variantGeneratorRegistry = $variantGeneratorRegistry;
        $this->variantConfigurationRepository = $variantConfigurationRepository;
        $this->workflowRegistry = $workflowRegistry;
        $this->uploadedImagesFilesystem = $uploadedImagesFilesystem;
        $this->processedImagesFilesystem = $processedImagesFilesystem;
        $this->processableResourceCollection = $processableResourceCollection;
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

        $variantConfiguration = $this->variantConfigurationRepository->findNewest();
        if (null === $variantConfiguration) {
            throw new UnrecoverableMessageHandlingException('No variant configuration saved in the database');
        }

        $variantCollection = $variantConfiguration->getVariantCollection();
        Assert::notNull($variantCollection);

        /**
         * todo This is just a check - I am not sure this check should be here
         *
         * @var Variant $variant
         */
        foreach ($variantCollection as $variant) {
            if (!$this->variantGeneratorRegistry->has($variant->generator)) {
                throw new \RuntimeException(sprintf(
                    'The variant "%s" has defined its generator to be "%s", but no such generator has been registered.',
                    $variant->name,
                    $variant->generator
                ));
            }
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

            $processableResource = $this->processableResourceCollection->get($image);

            /** @var VariantGeneratorInterface $variantGenerator */
            foreach ($this->variantGeneratorRegistry as $variantGenerator) {
                $variants = $processableResource->getVariantCollection()->getByGenerator($variantGenerator);

                foreach ($variantGenerator->generate($image, $imageFile, $variants) as $file) {
                    $this->processedImagesFilesystem->write(sprintf(
                        '%s/%s',
                        $file->getVariant(),
                        ImageVariantFile::replaceExtension((string) $image->getPath(), $file->getFileType())
                    ), file_get_contents($file->getPathname()), true);

                    @unlink($file->getPathname());
                }
            }

            $image->setVariantConfiguration($variantConfiguration);

            $workflow->apply($image, ProcessWorkflow::TRANSITION_FINISH);
            $manager->flush();
        } catch (\Throwable $e) {
            $workflow->apply($image, ProcessWorkflow::TRANSITION_FAIL);
            $manager->flush();

            throw ImageProcessingFailedException::fromCommand($message, $e);
        }
    }
}
