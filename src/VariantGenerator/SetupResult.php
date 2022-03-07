<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\VariantGenerator;

class SetupResult implements SetupResultInterface
{
    private string $generatorName;

    /** @var array<string, string> */
    private array $messages = [];

    public function __construct(string $generatorName)
    {
        $this->generatorName = $generatorName;
    }

    public function getGeneratorName(): string
    {
        return $this->generatorName;
    }

    public function addMessage(string $variantName, string $message): void
    {
        $this->messages[$variantName] = $message;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }
}
