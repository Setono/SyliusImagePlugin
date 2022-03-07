<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\VariantGenerator;

interface SetupResultInterface
{
    public function getGeneratorName(): string;

    /** @return array<string, string> */
    public function getMessages(): array;
}
