<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\DependencyInjection\Compiler;

use Setono\SyliusImagePlugin\Model\ImageInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Webmozart\Assert\Assert;

final class UpdateImageResourcesParameterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('setono_sylius_image.image_resources')
            || !$container->hasParameter('setono_sylius_image.presets')
            || !$container->hasParameter('sylius.resources')
        ) {
            return;
        }

        /** @var array<string, array{classes: array{model: class-string}}> $resources */
        $resources = $container->getParameter('sylius.resources');

        /**
         * The name of the preset is the key on the setono_sylius_image.presets array
         *
         * @psalm-suppress PossiblyInvalidArgument
         *
         * @var list<string> $presets
         */
        $presets = array_keys($container->getParameter('setono_sylius_image.presets'));

        /** @var array<string, array{name: string, presets: list<string>}> $imageResources */
        $imageResources = $container->getParameter('setono_sylius_image.image_resources');

        // if no image resources has been defined, we will add all resources that implements the \Setono\SyliusImagePlugin\Model\ImageInterface
        if ([] === $imageResources) {
            foreach ($resources as $name => $resource) {
                if (is_a($resource['classes']['model'], ImageInterface::class, true)) {
                    $imageResources[$name] = ['presets' => $presets];
                }
            }
        }

        foreach ($imageResources as $name => &$imageResource) {
            // validate that the presets added for an image resource are in fact configured presets
            foreach ($imageResource['presets'] as $preset) {
                Assert::inArray($preset, $presets, sprintf('On the image resource %s, you have added the preset %s, but this is not a configured preset. Configured presets are: [%s]', $name, $preset, implode(', ', $presets)));
            }

            if ([] === $imageResource['presets']) {
                $imageResource['presets'] = $presets;
            }

            $imageResource['class'] = $resources[$name]['classes']['model'];
        }
        unset($imageResource);

        $container->setParameter('setono_sylius_image.image_resources', $imageResources);
    }
}
