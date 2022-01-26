<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Processor;

use Setono\SyliusImagePlugin\Model\ImageInterface;

final class CloudflareProcessor implements ProcessorInterface
{
    public function process(ImageInterface $image): void
    {
        if ($image->isProcessed()) {
            return;
        }

        echo sprintf("Processing: %s\n", (string) $image->getPath());

        $image->setProcessed();
    }
}
