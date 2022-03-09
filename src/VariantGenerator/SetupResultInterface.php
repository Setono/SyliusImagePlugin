<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\VariantGenerator;

interface SetupResultInterface
{
    public function getGenerator(): VariantGeneratorInterface;

    /** @return list<string> */
    public function getMessages(): array;

    public function hasMessages(): bool;
}
