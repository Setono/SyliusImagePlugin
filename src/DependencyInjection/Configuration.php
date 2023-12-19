<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\DependencyInjection;

use Setono\SyliusImagePlugin\Config\Preset;
use Setono\SyliusImagePlugin\EventListener\Doctrine\ProcessUpdatedImageListener;
use Setono\SyliusImagePlugin\EventListener\Doctrine\RemoveProcessedImagesListener;
use Setono\SyliusImagePlugin\Model\ImageInterface;
use Setono\SyliusImagePlugin\Model\PresetConfiguration;
use Setono\SyliusImagePlugin\Repository\PresetConfigurationRepository;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Bundle\ResourceBundle\Form\Type\DefaultResourceType;
use Sylius\Component\Resource\Factory\Factory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('setono_sylius_image');
        $rootNode = $treeBuilder->getRootNode();

        /**
         * @psalm-suppress MixedMethodCall,PossiblyNullReference,PossiblyUndefinedMethod
         */
        $rootNode
            ->addDefaultsIfNotSet()
            ->fixXmlConfig('available_variant')
            ->children()
                ->scalarNode('default_image_generator')
                    ->info('The service id of the default image generator. If you define more than one image generator, you must configure a default image generator')
                    ->cannotBeEmpty()
                    ->defaultNull()
                ->end()
                ->arrayNode('listeners')
                    ->canBeDisabled()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('update_image')
                            ->info(sprintf('When you persist or update an image, the %s will automatically dispatch a processing message to the message queue for the respective image. If you don\'t want this listener enabled, set this option to false', ProcessUpdatedImageListener::class))
                            ->defaultTrue()
                        ->end()
                        ->booleanNode('remove_processed_images')
                            ->info(sprintf('When you delete an image inside Sylius, the listener %s will try to remove the processed image files. If you don\'t want this listener enabled, set this option to false', RemoveProcessedImagesListener::class))
                            ->defaultTrue()
                        ->end()
                        ->booleanNode('purge_liip_imagine_cache')
                            ->info('When an image has been processed the listener will attempt to purge the LiipImagine cache for relevant variants. If you don\'t want this listener enabled, set this option to false')
                            ->defaultTrue()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('presets') // todo we need to allow to change the 'fit' for presets
                    ->info('The presets you want to use throughout your application')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('name')
                                ->info('The name of the preset. This is what you use when you want to output an image in your application')
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('image_generator')
                                ->info('The service id of the image generator to use for this preset. If none is supplied, the default image generator will be used')
                                ->cannotBeEmpty()
                            ->end()
                            // todo validate that _either_ width or height is set
                            ->integerNode('width')
                                ->info('The width for this preset')
                                ->min(1)
                            ->end()
                            ->integerNode('height')
                                ->info('The height for this preset')
                                ->min(1)
                            ->end()
                            ->arrayNode('formats')
                                ->info('The formats that must be generated for this preset')
                                ->requiresAtLeastOneElement()
                                ->beforeNormalization()->castToArray()->end()
                                ->defaultValue([Preset::FORMAT_JPG, Preset::FORMAT_WEBP])
                                ->scalarPrototype()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('image_resources')
                    ->info(sprintf('The Sylius image resources you want to process using this plugin. If no resources are specified, all resources which implements %s are processed for all configured presets', ImageInterface::class))
                    ->useAttributeAsKey('name')
                    ->beforeNormalization()->castToArray()->end()
                    ->defaultValue([])
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('name')
                                ->info(sprintf('The name of the sylius image resource, e.g. "sylius.product_image". Must implement %s', ImageInterface::class))
                                ->cannotBeEmpty()
                            ->end()
                            ->arrayNode('presets')
                                ->info('A list of the presets you want to generate for this resource. If nothing is specified, all defined presets will be generated.')
                                ->scalarPrototype()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
        ;

        $this->addResourcesSection($rootNode);

        return $treeBuilder;
    }

    private function addResourcesSection(ArrayNodeDefinition $node): void
    {
        /**
         * @psalm-suppress MixedMethodCall,PossiblyUndefinedMethod,PossiblyNullReference
         */
        $node
            ->children()
                ->arrayNode('resources')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('preset_configuration')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->variableNode('options')->end()
                                ->arrayNode('classes')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('model')->defaultValue(PresetConfiguration::class)->cannotBeEmpty()->end()
                                        ->scalarNode('controller')->defaultValue(ResourceController::class)->cannotBeEmpty()->end()
                                        ->scalarNode('repository')->defaultValue(PresetConfigurationRepository::class)->cannotBeEmpty()->end()
                                        ->scalarNode('form')->defaultValue(DefaultResourceType::class)->end()
                                        ->scalarNode('factory')->defaultValue(Factory::class)->end()
        ;
    }
}
