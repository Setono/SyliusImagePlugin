<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Processor;

use Setono\SyliusImagePlugin\Model\ImageInterface;

interface ProcessorInterface
{
    public function process(ImageInterface $image): void;
}
