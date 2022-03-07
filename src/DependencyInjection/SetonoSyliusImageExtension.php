<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\DependencyInjection;

use Setono\SyliusImagePlugin\Workflow\ProcessWorkflow;
use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class SetonoSyliusImageExtension extends AbstractResourceExtension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        /**
         * @psalm-suppress PossiblyNullArgument
         *
         * @var array{driver: string, listeners: array{enabled: bool, update_image: bool, remove_processed_images: bool, purge_liip_imagine_cache: bool}, resources: array<string, mixed>, public_processed_path: string, filter_sets: array} $config
         */
        $config = $this->processConfiguration($this->getConfiguration([], $container), $configs);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $container->setParameter('setono_sylius_image.public_processed_path', rtrim($config['public_processed_path'], '/'));
        $container->setParameter('setono_sylius_image.filter_sets', $config['filter_sets']);

        $this->registerResources('setono_sylius_image', $config['driver'], $config['resources'], $container);

        $loader->load('services.xml');

        if (true === $config['listeners']['enabled'] && true === $config['listeners']['update_image']) {
            $loader->load('services/conditional/update_image_listener.xml');
        }

        if (true === $config['listeners']['enabled'] && true === $config['listeners']['remove_processed_images']) {
            $loader->load('services/conditional/remove_processed_images_listener.xml');
        }

        if (true === $config['listeners']['enabled'] && true === $config['listeners']['purge_liip_imagine_cache']) {
            $loader->load('services/conditional/purge_liip_imagine_cache_subscriber.xml');
        }
    }

    public function prepend(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('framework', [
            'workflows' => ProcessWorkflow::getConfig(),
        ]);
    }
}
