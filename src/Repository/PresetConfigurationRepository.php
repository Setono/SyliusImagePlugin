<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Repository;

use Setono\SyliusImagePlugin\Model\PresetConfigurationInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Webmozart\Assert\Assert;

class PresetConfigurationRepository extends EntityRepository implements PresetConfigurationRepositoryInterface
{
    public function findNewest(): ?PresetConfigurationInterface
    {
        $obj = $this->createQueryBuilder('o')
            ->addOrderBy('o.createdAt', 'desc')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        Assert::nullOrIsInstanceOf($obj, PresetConfigurationInterface::class);

        return $obj;
    }
}
