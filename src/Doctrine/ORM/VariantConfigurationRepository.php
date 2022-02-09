<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Doctrine\ORM;

use Setono\SyliusImagePlugin\Model\VariantConfigurationInterface;
use Setono\SyliusImagePlugin\Repository\VariantConfigurationRepositoryInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Webmozart\Assert\Assert;

class VariantConfigurationRepository extends EntityRepository implements VariantConfigurationRepositoryInterface
{
    public function findNewest(): ?VariantConfigurationInterface
    {
        $obj = $this->createQueryBuilder('o')
            ->addOrderBy('o.createdAt', 'desc')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        Assert::nullOrIsInstanceOf($obj, VariantConfigurationInterface::class);

        return $obj;
    }
}
