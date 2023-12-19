<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Command;

use Setono\SyliusImagePlugin\Synchronizer\VariantConfigurationSynchronizationResultInterface;
use Setono\SyliusImagePlugin\Synchronizer\VariantConfigurationSynchronizerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class SynchronizeVariantConfigurationCommand extends Command
{
    protected static $defaultName = 'setono:sylius-image:sync-variant-configuration';

    public const OPTION_SKIP_SETUP = 'skip-setup';

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

    protected function configure(): void
    {
        $this->addOption(self::OPTION_SKIP_SETUP, null, InputOption::VALUE_NONE, 'Skip setup when synchronizing variant configuration');

        $this->setHelp(
            <<<'EOF'
The <info>%command.name%</> command  will compare your plugin configuration with the newest database configuration and if there are changes a new
database configuration will be saved and consequently be used as the newest configuration. This also implies that if you
pass this flag and a new configuration is saved, all images will be processed. NOTE that this does NOT mean that
all variants are reprocessed, just the new variants when comparing the new configuration to the old configuration.

Example
-------

<comment>Old configuration</comment>

setono_sylius_image:
    available_variants:
        sylius_shop_product_tiny_thumbnail: ~
        sylius_shop_product_small_thumbnail: ~
        sylius_shop_product_thumbnail: ~

<comment>New configuration</comment>

setono_sylius_image:
    available_variants:
        sylius_shop_product_tiny_thumbnail: ~
        sylius_shop_product_small_thumbnail: ~
        sylius_shop_product_thumbnail: ~
        sylius_shop_product_large_thumbnail: ~

Here the <comment>sylius_shop_product_large_thumbnail</comment> will be processed for all images.
EOF
        );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $skipSetup = true !== $input->getOption(self::OPTION_SKIP_SETUP);

        $synchronizeResult = $this->variantConfigurationSynchronizer->synchronize($skipSetup);

        self::reportSynchronizationResult($synchronizeResult, $this->io);

        return 0;
    }

    public static function reportSynchronizationResult(VariantConfigurationSynchronizationResultInterface $synchronizeResult, SymfonyStyle $io): void
    {
        if ($synchronizeResult->hasMessages()) {
            $io->writeln('Messages from synchronization');
            $io->listing($synchronizeResult->getMessages());
        }

        foreach ($synchronizeResult->getSetupResults() as $setupResult) {
            if ($setupResult->hasMessages()) {
                $io->writeln('Messages from generator setup');
                $io->listing($setupResult->getMessages());
            } else {
                $io->writeln('Nothing to report from generator');
            }
        }
    }
}
