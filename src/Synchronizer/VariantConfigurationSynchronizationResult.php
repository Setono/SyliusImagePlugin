<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Synchronizer;

use Setono\SyliusImagePlugin\VariantGenerator\SetupResultInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

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

    public function isStopExecution(): bool
    {
        foreach ($this->setupResults as $setupResult) {
            if ($setupResult->isStopExecution()) {
                return true;
            }
        }

        return false;
    }

    public function addSetupResult(SetupResultInterface $setupResult): void
    {
        $this->setupResults[$setupResult->getGeneratorName()] = $setupResult;
    }

    public function reportResults(SymfonyStyle $io): void
    {
        if (!empty($this->messages)) {
            $io->writeln('Messages from synchronization');
            $io->definitionList(...$this->messages);
        }

        foreach ($this->setupResults as $setupResult) {
            $io->writeln(sprintf('Messages from \'%s\' generator setup', $setupResult->getGeneratorName()));
            $setupResult->reportResults($io);
        }
    }
}
