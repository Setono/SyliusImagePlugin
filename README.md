# Sylius Image Plugin

[![Latest Version][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-github-actions]][link-github-actions]
[![Code Coverage][ico-code-coverage]][link-code-coverage]

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

### Step 5: Update your database schema

```bash
$ php bin/console doctrine:migrations:diff
$ php bin/console doctrine:migrations:migrate
```

[ico-version]: https://poser.pugx.org/setono/sylius-image-plugin/v/stable
[ico-license]: https://poser.pugx.org/setono/sylius-image-plugin/license
[ico-github-actions]: https://github.com/Setono/SyliusImagePlugin/workflows/build/badge.svg
[ico-code-coverage]: https://codecov.io/gh/Setono/SyliusImagePlugin/branch/master/graph/badge.svg

[link-packagist]: https://packagist.org/packages/setono/sylius-image-plugin
[link-github-actions]: https://github.com/Setono/SyliusImagePlugin/actions
[link-code-coverage]: https://codecov.io/gh/Setono/SyliusImagePlugin
