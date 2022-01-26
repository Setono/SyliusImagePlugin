<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusImagePlugin\Application\Model;

use Doctrine\ORM\Mapping as ORM;
use Setono\SyliusImagePlugin\Model\ImageInterface;
use Setono\SyliusImagePlugin\Model\ImageTrait;
use Sylius\Component\Core\Model\ProductImage as BaseProductImage;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_product_image")
 */
class ProductImage extends BaseProductImage implements ImageInterface
{
    use ImageTrait;
}
