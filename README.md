# Sylius Image Plugin

[![Latest Version][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-github-actions]][link-github-actions]
[![Code Coverage][ico-code-coverage]][link-code-coverage]

This library will optimize your images and it works seamlessly with a standard Sylius installation because it has
wise fallbacks. Out of the box this plugin uses Cloudflare, so to use it you need a Cloudflare account with the
[images subscription](https://www.cloudflare.com/products/cloudflare-images/).

## Installation

### Step 1: Download the plugin

Open a command console, enter your project directory and execute the following command to download the latest stable version of this plugin:

```bash
composer require setono/sylius-image-plugin
```

### Step 2: Enable the plugin

Then, enable the plugin by adding it to the list of registered plugins/bundles
in `config/bundles.php` file of your project before (!) `SyliusGridBundle`:

```php
<?php
$bundles = [
    // ...
    
    Setono\SyliusImagePlugin\SetonoSyliusImagePlugin::class => ['all' => true],
    Sylius\Bundle\GridBundle\SyliusGridBundle::class => ['all' => true],
    
    // ...
];
```

### Step 3: Configure plugin

```yaml
# config/packages/setono_sylius_image.yaml
imports:
    - { resource: "@SetonoSyliusImagePlugin/Resources/config/app/config.yaml" }
```

### Step 4: Import routing

```yaml
# config/routes/setono_sylius_image.yaml
setono_sylius_image:
    resource: "@SetonoSyliusImagePlugin/Resources/config/routes.yaml"
```

## Usage

In this plugin we have a concept named _variants_. These are image variants, i.e. different sizes of images. In a default
Sylius installation you use the Liip Imagine Bundle which has a concept of filter sets. In the context of this plugin,
these two concepts are more or less similar.

To use the plugin, you have to tell the plugin which variants (filter sets) should be available for optimization and
which image resources should be optimized. Here is an example:

### Configuration

```yaml
# config/packages/setono_sylius_image.yaml
setono_sylius_image:
    available_variants:
        sylius_shop_product_original: ~
        sylius_shop_product_tiny_thumbnail: ~
        sylius_shop_product_small_thumbnail: ~
        sylius_shop_product_thumbnail: ~
        sylius_shop_product_large_thumbnail: ~
    image_resources:
        sylius.product_image:
            variants:
                - 'sylius_shop_product_original'
                - 'sylius_shop_product_tiny_thumbnail'
                - 'sylius_shop_product_small_thumbnail'
                - 'sylius_shop_product_thumbnail'
                - 'sylius_shop_product_large_thumbnail'
```

Effectively this means that for each product image you will have five image variants being optimized. Other variants
like `sylius_admin_product_thumbnail` or `sylius_large` won't be optimized.

### Implement code changes

For the plugin to work the image resources needs to implement an interface. Here is an example of product image resource:

```php
<?php

declare(strict_types=1);

namespace App\Entity\Product;

use Doctrine\ORM\Mapping as ORM;
use Setono\SyliusImagePlugin\Model\ImageInterface as SetonoSyliusImagePluginImageInterface;
use Setono\SyliusImagePlugin\Model\ImageTrait as SetonoSyliusImagePluginImageTrait;
use Sylius\Component\Core\Model\ProductImage as BaseProductImage;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_product_image")
 */
class ProductImage extends BaseProductImage implements SetonoSyliusImagePluginImageInterface
{
    use SetonoSyliusImagePluginImageTrait;
}
```

## Final

### Update your database schema

```bash
$ php bin/console doctrine:migrations:diff
$ php bin/console doctrine:migrations:migrate
```

[ico-version]: https://poser.pugx.org/setono/sylius-image-plugin/v/stable
[ico-license]: https://poser.pugx.org/setono/sylius-image-plugin/license
[ico-github-actions]: https://github.com/Setono/SyliusImagePlugin/workflows/build/badge.svg
[ico-code-coverage]: https://codecov.io/gh/Setono/SyliusImagePlugin/branch/0.2.x/graph/badge.svg

[link-packagist]: https://packagist.org/packages/setono/sylius-image-plugin
[link-github-actions]: https://github.com/Setono/SyliusImagePlugin/actions
[link-code-coverage]: https://codecov.io/gh/Setono/SyliusImagePlugin
