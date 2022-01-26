<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Command;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Setono\DoctrineObjectManagerTrait\ORM\ORMManagerTrait;
use Setono\SyliusImagePlugin\Message\Command\ProcessImage;
use Setono\SyliusImagePlugin\Model\ImageInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

final class ProcessCommand extends Command
{
    use ORMManagerTrait;

    protected static $defaultName = 'setono:sylius-image:process';

    protected static $defaultDescription = 'Will process all image variants';

    private MessageBusInterface $commandBus;

    /** @var array<string, array> */
    private array $filterSets;

    /** @var array<string, array{classes: array{model: string}}> */
    private array $resources;

    /**
     * @param array<string, array> $filterSets
     * @param array<string, array{classes: array{model: string}}> $resources
     */
    public function __construct(ManagerRegistry $managerRegistry, MessageBusInterface $commandBus, array $filterSets, array $resources)
    {
        parent::__construct();

        $this->managerRegistry = $managerRegistry;
        $this->commandBus = $commandBus;
        $this->filterSets = $filterSets;
        $this->resources = $resources;
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->resources as $resource) {
            if (!is_a($resource['classes']['model'], ImageInterface::class, true)) {
                continue;
            }

            $this->processResource($resource['classes']['model']);
        }

        return 0;
    }

    /**
     * @param class-string $class
     */
    private function processResource(string $class): void
    {
        $manager = $this->getManager($class);
        $repository = $manager->getRepository($class);
        Assert::isInstanceOf($repository, EntityRepository::class);

        foreach ($this->getImages($repository, $manager) as $image) {
            $this->commandBus->dispatch(ProcessImage::fromImage($image));
        }
    }

    /**
     * @return iterable<ImageInterface>
     */
    private function getImages(EntityRepository $repository, ObjectManager $manager): iterable
    {
        $firstResult = 0;
        $maxResults = 100;

        $qb = $repository->createQueryBuilder('o')->andWhere('o.processed = false');
        $qb->setMaxResults($maxResults);

        do {
            $qb->setFirstResult($firstResult);

            $images = $qb->getQuery()->getResult();
            Assert::isArray($images);

            /** @var ImageInterface $image */
            foreach ($images as $image) {
                yield $image;
            }

            $firstResult += $maxResults;

            $manager->clear();
        } while ([] !== $images);
    }
}
