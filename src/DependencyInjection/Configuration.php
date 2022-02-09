<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\DependencyInjection;

use Setono\SyliusImagePlugin\Doctrine\ORM\VariantConfigurationRepository;
use Setono\SyliusImagePlugin\Model\VariantConfiguration;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Bundle\ResourceBundle\Form\Type\DefaultResourceType;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;
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
            ->fixXmlConfig('filter_set')
            ->children()
                ->scalarNode('driver')
                    ->defaultValue(SyliusResourceBundle::DRIVER_DOCTRINE_ORM)
                ->end()
                ->scalarNode('public_processed_path')
                    ->defaultValue('/media/image/processed')
                    ->info(<<<INFO
This is the path where processed images are saved in a web context. I.e. if you can access a processed image here:
https://example.com/media/image/processed/sylius_shop_large/ae/ef/a7f7d7a3e.jpg,
then your public_processed_path should be /media/image/processed. As the name implies the path must be publicly accessible
INFO)
                    ->cannotBeEmpty()
                ->end()
                ->arrayNode('filter_sets')
                    ->info('The LiipImagineBundle filter sets you want to process using this plugin')
                    ->useAttributeAsKey('name')
                    ->beforeNormalization()->castToArray()->end()
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('name')
                                ->info('The name of the filter set')
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('generator')
                                ->info('The name of the variant generator to use for this filter set')
                                ->cannotBeEmpty()
                                ->defaultValue('cloudflare')
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
         * @psalm-suppress MixedMethodCall
         * @psalm-suppress PossiblyUndefinedMethod
         * @psalm-suppress PossiblyNullReference
         */
        $node
            ->children()
                ->arrayNode('resources')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('variant_configuration')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->variableNode('options')->end()
                                ->arrayNode('classes')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('model')->defaultValue(VariantConfiguration::class)->cannotBeEmpty()->end()
                                        ->scalarNode('controller')->defaultValue(ResourceController::class)->cannotBeEmpty()->end()
                                        ->scalarNode('repository')->defaultValue(VariantConfigurationRepository::class)->cannotBeEmpty()->end()
                                        ->scalarNode('form')->defaultValue(DefaultResourceType::class)->end()
                                        ->scalarNode('factory')->defaultValue(Factory::class)->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
