<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Twig;

use Gaufrette\FilesystemInterface;
use Liip\ImagineBundle\Templating\FilterExtension;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\RuntimeExtensionInterface;

final class Runtime implements RuntimeExtensionInterface
{
    private FilesystemInterface $filesystem;

    private FilterExtension $filterExtension;

    private string $publicProcessedPath;

    private RequestStack $requestStack;

    public function __construct(
        FilesystemInterface $filesystem,
        FilterExtension $filterExtension,
        RequestStack $requestStack,
        string $publicProcessedPath
    ) {
        $this->filesystem = $filesystem;
        $this->filterExtension = $filterExtension;
        $this->requestStack = $requestStack;
        $this->publicProcessedPath = $publicProcessedPath;
    }

    public function imagePath(string $relativePath, string $variant): string // todo return value object instead?
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
            $tmpRelativePath = self::replaceExtension($relativePath, $candidate);
            if ($this->filesystem->has(sprintf('%s/%s', $variant, $tmpRelativePath))) {
                return sprintf('%s/%s/%s', $this->publicProcessedPath, $variant, $tmpRelativePath);
            }
        }

        if ($this->filesystem->has(sprintf('%s/%s', $variant, $relativePath))) {
            return sprintf('%s/%s/%s', $this->publicProcessedPath, $variant, $relativePath);
        }

        return $this->filterExtension->filter($relativePath, $variant); // todo add other arguments
    }

    private static function replaceExtension(string $path, string $newExtension): string
    {
        $pathInfo = pathinfo($path);

        return sprintf('%s/%s.%s', $pathInfo['dirname'], $pathInfo['filename'], $newExtension);
    }
}
