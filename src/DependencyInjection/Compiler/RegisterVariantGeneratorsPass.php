<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class RegisterVariantGeneratorsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('setono_sylius_image.registry.variant_generator')) {
            return;
        }

        $registry = $container->getDefinition('setono_sylius_image.registry.variant_generator');

        /** @var string $id */
        foreach (array_keys($container->findTaggedServiceIds('setono_sylius_image.variant_generator')) as $id) {
            $registry->addMethodCall('add', [new Reference($id)]);
        }
    }
}
