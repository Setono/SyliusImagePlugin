<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\DependencyInjection;

use Setono\SyliusImagePlugin\Workflow\ProcessWorkflow;
use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Webmozart\Assert\Assert;

final class SetonoSyliusImageExtension extends AbstractResourceExtension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        /**
         * @psalm-suppress PossiblyNullArgument
         *
         * @var array{listeners: array{enabled: bool, update_image: bool, remove_processed_images: bool, purge_liip_imagine_cache: bool}, resources: array<string, mixed>, public_processed_path: string, available_variants: array, image_resources: array<string, array{resource: string, variants: list<string>}>} $config
         */
        $config = $this->processConfiguration($this->getConfiguration([], $container), $configs);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $container->setParameter('setono_sylius_image.available_variants', $config['available_variants']);
        $container->setParameter('setono_sylius_image.image_resources', $config['image_resources']);

        $pathParamName = 'setono_sylius_image.public_processed_path';
        $publicProcessedPath = $container->getParameter($pathParamName);
        Assert::stringNotEmpty($publicProcessedPath);
        if (strpos($publicProcessedPath, '/') !== 0) {
            throw new \InvalidArgumentException(sprintf("The parameter '%s' must start with a / (Value: %s)", $pathParamName, $publicProcessedPath));
        }
        if (strlen($publicProcessedPath) > 1 // If someone (for some reason) wants '/' to be the public path
            && substr($publicProcessedPath, -1) === '/') {
            throw new \InvalidArgumentException(sprintf("The parameter '%s' must NOT end with a / (Value: %s)", $pathParamName, $publicProcessedPath));
        }

        $this->registerResources('setono_sylius_image', SyliusResourceBundle::DRIVER_DOCTRINE_ORM, $config['resources'], $container);

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
