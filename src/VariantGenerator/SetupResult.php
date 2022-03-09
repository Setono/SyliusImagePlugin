<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\VariantGenerator;

class SetupResult implements SetupResultInterface
{
    /** @var list<string> */
    private array $messages = [];

    private VariantGeneratorInterface $generator;

    public function __construct(VariantGeneratorInterface $generator)
    {
        $this->generator = $generator;
    }

    public function getGenerator(): VariantGeneratorInterface
    {
        return $this->generator;
    }

    public function addMessage(string $message): void
    {
        $this->messages[] = $message;
    }

    public function hasMessages(): bool
    {
        return !empty($this->messages);
    }

    public function getMessages(): array
    {
        return $this->messages;
    }
}
