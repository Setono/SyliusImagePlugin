<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusImagePlugin\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Setono\SyliusImagePlugin\DependencyInjection\SetonoSyliusImageExtension;

/**
 * See examples of tests and configuration options here: https://github.com/SymfonyTest/SymfonyDependencyInjectionTest
 */
final class SetonoSyliusImageExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions(): array
    {
        return [
            new SetonoSyliusImageExtension(),
        ];
    }

    /**
     * @test
     */
    public function after_loading_the_correct_parameter_has_been_set(): void
    {
        $this->load();

        $this->assertContainerBuilderHasParameter('setono_sylius_image.public_processed_path', '/media/image/processed');
        $this->assertContainerBuilderHasParameter('setono_sylius_image.filter_sets', []);
    }
}
