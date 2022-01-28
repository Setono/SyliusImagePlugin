<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\DependencyInjection;

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

        return $treeBuilder;
    }
}
