<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class RegisterImageGeneratorsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('setono_sylius_image.image_generator.registry')) {
            return;
        }

        $default = null;

        if ($container->hasParameter('setono_sylius_image.default_image_generator')) {
            $default = $container->getParameter('setono_sylius_image.default_image_generator');
        }

        $registry = $container->getDefinition('setono_sylius_image.image_generator.registry');

        /** @var list<string> $ids */
        $ids = array_keys($container->findTaggedServiceIds('setono_sylius_image.image_generator'));
        if (null === $default && count($ids) > 0) {
            if (count($ids) > 1) {
                throw new \RuntimeException('You need to define a default image generator at "setono_sylius_image.default_image_generator"');
            }

            $container->setParameter('setono_sylius_image.default_image_generator', $ids[0]);
        }

        foreach ($ids as $id) {
            $registry->addMethodCall('add', [$id, new Reference($id)]);
        }
    }
}
