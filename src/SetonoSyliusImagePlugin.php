<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin;

use Setono\SyliusImagePlugin\DependencyInjection\Compiler\RegisterVariantGeneratorsPass;
use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SetonoSyliusImagePlugin extends Bundle
{
    use SyliusPluginTrait;

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new RegisterVariantGeneratorsPass());
    }
}
