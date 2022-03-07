<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Synchronizer;

use Setono\SyliusImagePlugin\VariantGenerator\SetupResultInterface;

class VariantConfigurationSynchronizationResult implements VariantConfigurationSynchronizationResultInterface
{
    /** @var array<string, string> */
    private array $messages = [];

    /** @var array<string, SetupResultInterface> */
    private array $setupResults = [];

    public function addMessage(string $topic, string $message): void
    {
        $this->messages[$topic] = $message;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function addSetupResult(SetupResultInterface $setupResult): void
    {
        $this->setupResults[$setupResult->getGeneratorName()] = $setupResult;
    }

    public function getSetupResults(): array
    {
        return $this->setupResults;
    }
}
