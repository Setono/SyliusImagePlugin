<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Resolver;

use Gaufrette\FilesystemInterface;
use Setono\SyliusImagePlugin\File\ImageVariantFile;
use Symfony\Component\HttpFoundation\RequestStack;

final class ProcessedVariantPathResolver implements ProcessedVariantPathResolverInterface
{
    private FilesystemInterface $filesystem;

    private RequestStack $requestStack;

    private string $publicProcessedPath;

    public function __construct(
        FilesystemInterface $filesystem,
        RequestStack $requestStack,
        string $publicProcessedPath
    ) {
        $this->filesystem = $filesystem;
        $this->requestStack = $requestStack;
        $this->publicProcessedPath = $publicProcessedPath;
    }

    public function getPublicVariantPath(string $relativePath, string $variant): ?string
    {
        $candidates = [];

        $request = $this->requestStack->getMasterRequest();
        if (null !== $request) {
            $acceptHeaders = $request->headers->get('Accept');
            if (null !== $acceptHeaders) {
                if (strpos($acceptHeaders, 'image/avif') !== false) {
                    $candidates[] = 'avif';
                }

                if (strpos($acceptHeaders, 'image/webp') !== false) {
                    $candidates[] = 'webp';
                }
            }
        }

        // first check optimized image candidates
        foreach ($candidates as $candidate) {
            $tmpRelativePath = ImageVariantFile::replaceExtension($relativePath, $candidate);
            if ($this->filesystem->has(sprintf('%s/%s', $variant, $tmpRelativePath))) {
                return sprintf('%s/%s/%s', $this->publicProcessedPath, $variant, $tmpRelativePath);
            }
        }

        if ($this->filesystem->has(sprintf('%s/%s', $variant, $relativePath))) {
            return sprintf('%s/%s/%s', $this->publicProcessedPath, $variant, $relativePath);
        }

        return null;
    }
}
