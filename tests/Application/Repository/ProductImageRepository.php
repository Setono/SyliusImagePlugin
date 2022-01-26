<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusImagePlugin\Application\Repository;

use Setono\SyliusImagePlugin\Doctrine\ORM\ImageRepositoryTrait;
use Setono\SyliusImagePlugin\Repository\ImageRepositoryInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

class ProductImageRepository extends EntityRepository implements ImageRepositoryInterface
{
    use ImageRepositoryTrait;
}
