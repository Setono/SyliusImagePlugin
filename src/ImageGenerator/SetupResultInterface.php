<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\ImageGenerator;

interface SetupResultInterface
{
    public function getGenerator(): ImageGeneratorInterface;

    /** @return list<string> */
    public function getMessages(): array;

    public function hasMessages(): bool;
}
