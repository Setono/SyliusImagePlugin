<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Command;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Setono\DoctrineObjectManagerTrait\ORM\ORMManagerTrait;
use Setono\SyliusImagePlugin\DependencyInjection\SetonoSyliusImageExtension;
use Setono\SyliusImagePlugin\Model\ImageInterface;
use Setono\SyliusImagePlugin\Workflow\ProcessWorkflow;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Workflow\Registry;
use Webmozart\Assert\Assert;

final class TimeoutCommand extends Command
{
    use ORMManagerTrait;

    protected static $defaultName = 'setono:sylius-image:timeout';

    /** @var string|null */
    protected static $defaultDescription = 'This command will \'time out\' images that are hanging in the processing state above a given threshold';

    /**
     * It is set in the initialize method which is called before the execute method
     *
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private SymfonyStyle $io;

    private Registry $workflowRegistry;

    /** @var array<string, array{classes: array{model: string}}> */
    private array $resources;

    /**
     * This is the timeout threshold in minutes
     */
    private int $timeoutThreshold;

    /**
     * @param array<string, array{classes: array{model: string}}> $resources
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        Registry $workflowRegistry,
        array $resources,
        int $timeoutThreshold
    ) {
        parent::__construct();

        $this->managerRegistry = $managerRegistry;
        $this->workflowRegistry = $workflowRegistry;
        $this->resources = $resources;
        $this->timeoutThreshold = $timeoutThreshold;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var array<array-key, class-string> $resourcesToProcess */
        $resourcesToProcess = [];

        foreach ($this->resources as $resource) {
            if (!is_a($resource['classes']['model'], ImageInterface::class, true)) {
                continue;
            }

            $resourcesToProcess[] = $resource['classes']['model'];
        }

        if ([] === $resourcesToProcess) {
            $this->io->writeln(sprintf('No resources implements the interface %s', ImageInterface::class));

            return 0;
        }

        $dtThreshold = new \DateTimeImmutable(sprintf('-%d min', $this->timeoutThreshold));
        $this->io->info(sprintf(
            'Will timeout images that\'s been processing longer than %d minutes (i.e. processingStateUpdatedAt <= %s)',
            $this->timeoutThreshold,
            $dtThreshold->format('Y-m-d H:i:s')
        ));

        foreach ($resourcesToProcess as $resourceToProcess) {
            $this->processResource($resourceToProcess);
        }

        return 0;
    }

    /**
     * @param class-string $class
     */
    private function processResource(string $class): void
    {
        $this->io->section(sprintf('Processing resource: %s', $class));

        $manager = $this->getManager($class);

        /** @var ObjectRepository|EntityRepository $repository */
        $repository = $manager->getRepository($class);
        Assert::isInstanceOf($repository, EntityRepository::class);

        $workflow = null;

        try {
            $i = 0;
            foreach ($this->getImages($repository, $manager) as $image) {
                if ($workflow === null) {
                    $workflow = $this->workflowRegistry->get($image, ProcessWorkflow::NAME);
                }

                if ($workflow->can($image, ProcessWorkflow::TRANSITION_FAIL)) {
                    $workflow->apply($image, ProcessWorkflow::TRANSITION_FAIL);
                }

                if ($i % 50 === 0) {
                    $manager->flush();
                }

                ++$i;
            }
        } finally {
            $manager->flush();
        }

        $this->io->success(sprintf('%d image%s was timed out...', $i, $i === 1 ? '' : 's'));
    }

    /**
     * @return iterable<ImageInterface>
     */
    private function getImages(EntityRepository $repository, ObjectManager $manager): iterable
    {
        $firstResult = 0;
        $maxResults = 100;

        $qb = $repository->createQueryBuilder('o');
        $qb
            ->andWhere('o.processingState = :processingState')
            ->andWhere('o.processingStateUpdatedAt <= :threshold')
            ->setParameter('processingState', [ImageInterface::PROCESSING_STATE_PROCESSING])
            ->setParameter('threshold', new \DateTimeImmutable(sprintf('-%d min', $this->timeoutThreshold)))
            ->setMaxResults($maxResults)
        ;

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
