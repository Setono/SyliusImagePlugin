<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Synchronizer;

use Setono\SyliusImagePlugin\VariantGenerator\SetupResultInterface;

class VariantConfigurationSynchronizationResult implements VariantConfigurationSynchronizationResultInterface
{
    /** @var list<string> */
    private array $messages = [];

    /** @var list<SetupResultInterface> */
    private array $setupResults = [];

    public function addMessage(string $message): void
    {
        $this->messages[] = $message;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function hasMessages(): bool
    {
        return !empty($this->messages);
    }

    public function addSetupResult(SetupResultInterface $setupResult): void
    {
        $this->setupResults[] = $setupResult;
    }

    public function getSetupResults(): array
    {
        return $this->setupResults;
    }
}
