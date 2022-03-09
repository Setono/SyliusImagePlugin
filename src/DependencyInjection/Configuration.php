<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\DependencyInjection;

use Setono\SyliusImagePlugin\Doctrine\ORM\VariantConfigurationRepository;
use Setono\SyliusImagePlugin\EventListener\Doctrine\ProcessUpdatedImageListener;
use Setono\SyliusImagePlugin\EventListener\Doctrine\RemoveProcessedImagesListener;
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
            ->fixXmlConfig('available_variant')
            ->children()
                ->scalarNode('driver')
                    ->defaultValue(SyliusResourceBundle::DRIVER_DOCTRINE_ORM)
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
                ->scalarNode('public_processed_path')
                    ->defaultValue('/media/image/processed')
                    ->info(<<<INFO
This is the path where processed images are saved in a web context. I.e. if you can access a processed image here:
https://example.com/media/image/processed/sylius_shop_large/ae/ef/a7f7d7a3e.jpg,
then your public_processed_path should be /media/image/processed. As the name implies the path must be publicly accessible
INFO)
                    ->cannotBeEmpty()
                ->end()
                ->arrayNode('available_variants')
                    ->info('The variants you want to make available for this plugin. Variants MUST, by name, correspond with filter sets defined in the LiipImagineBundle')
                    ->useAttributeAsKey('name')
                    ->beforeNormalization()->castToArray()->end()
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('name')
                                ->info('The name of the variant. Must correspond with a LiipImagine filter set')
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('generator')
                                ->info('The name of the variant generator to use for this variant')
                                ->cannotBeEmpty()
                                ->defaultValue('cloudflare')
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('image_resources')
                    ->info('The sylius image resources you want to process using this plugin. If no resources are specified, all resources which implements ImageInterface are processed')
                    ->useAttributeAsKey('resource')
                    ->beforeNormalization()->castToArray()->end()
                    ->defaultValue([])
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('resource')
                                ->info('The name of the sylius image resource, i.e. \'sylius.product_image\'. Must implement ImageInterface')
                                ->cannotBeEmpty()
                            ->end()
                            ->arrayNode('variants')
                                ->info('A list of the variants you want to generate for this resource. If nothing is specified, all \'available_variants\' will be generated.')
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
