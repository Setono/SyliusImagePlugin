<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\VariantGenerator;

use Symfony\Component\Console\Style\SymfonyStyle;

class SetupResult implements SetupResultInterface
{
    private string $generatorName;

    private bool $stopExecution = false;

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

    public function isStopExecution(): bool
    {
        return $this->stopExecution;
    }

    public function setStopExecution(bool $stopExecution): void
    {
        $this->stopExecution = $stopExecution;
    }

    public function addMessage(string $variantName, string $message): void
    {
        $this->messages[$variantName] = $message;
    }

    public function reportResults(SymfonyStyle $io): void
    {
        if (empty($this->messages)) {
            $io->writeln('Nothing to report');
        } else {
            $io->definitionList(...$this->messages);
        }
    }
}
