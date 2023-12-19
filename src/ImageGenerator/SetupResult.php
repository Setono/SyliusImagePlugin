<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\ImageGenerator;

class SetupResult implements SetupResultInterface
{
    /** @var list<string> */
    private array $messages = [];

    private ImageGeneratorInterface $generator;

    public function __construct(ImageGeneratorInterface $generator)
    {
        $this->generator = $generator;
    }

    public function getGenerator(): ImageGeneratorInterface
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
