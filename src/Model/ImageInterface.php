<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Model;

use Sylius\Component\Core\Model\ImageInterface as BaseImageInterface;

interface ImageInterface extends BaseImageInterface
{
    /**
     * Returns true if all variants for this image are processed
     */
    public function isProcessed(): bool;

    public function setProcessed(bool $processed = true): void;
}
