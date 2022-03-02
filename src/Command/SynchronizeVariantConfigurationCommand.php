<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Command;

use Setono\SyliusImagePlugin\Synchronizer\VariantConfigurationSynchronizerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class SynchronizeVariantConfigurationCommand extends Command
{
    protected static $defaultName = 'setono:sylius-image:sync-variant-configuration';

    /** @var string|null */
    protected static $defaultDescription = 'Will synchronize the application configuration into the database';

    /**
     * It is set in the initialize method which is called before the execute method
     *
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private SymfonyStyle $io;

    private VariantConfigurationSynchronizerInterface $variantConfigurationSynchronizer;

    public function __construct(VariantConfigurationSynchronizerInterface $variantConfigurationSynchronizer)
    {
        parent::__construct();

        $this->variantConfigurationSynchronizer = $variantConfigurationSynchronizer;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->variantConfigurationSynchronizer->synchronize();

        $this->io->success('Variant configuration synchronized');

        return 0;
    }
}
