<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Twig;

use Setono\SyliusImagePlugin\Model\ImageInterface;
use Sylius\Component\Core\Model\ImageInterface as BaseImageInterface;
use Twig\Extension\RuntimeExtensionInterface;

final class Runtime implements RuntimeExtensionInterface
{
    public function image(BaseImageInterface $image, string $variant): string // todo return value object instead?
    {
        if ($image instanceof ImageInterface) {
            if ($image->isProcessed()) {
                return '/media/image/processed/' . $variant . '/' . (string) $image->getPath(); // todo get these paths with Gaufrette filesystem
            }

            return '/media/image/' . (string) $image->getPath();
        }

        // todo return image path from liip imagine?
        return '/media/image/' . (string) $image->getPath();
    }
}
