<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Command;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Setono\DoctrineObjectManagerTrait\ORM\ORMManagerTrait;
use Setono\SyliusImagePlugin\Config\ImageResource;
use Setono\SyliusImagePlugin\Config\ImageResourceRegistryInterface;
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
    protected static $defaultDescription = 'This command will "time out" images that are hanging in the processing state above a given threshold';

    /**
     * It is set in the initialize method which is called before the execute method
     *
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private SymfonyStyle $io;

    private ImageResourceRegistryInterface $imageResourceRegistry;

    private Registry $workflowRegistry;

    /**
     * This is the timeout threshold in minutes
     */
    private int $timeoutThreshold;

    public function __construct(
        ManagerRegistry $managerRegistry,
        ImageResourceRegistryInterface $imageResourceRegistry,
        Registry $workflowRegistry,
        int $timeoutThreshold
    ) {
        parent::__construct();

        $this->managerRegistry = $managerRegistry;
        $this->imageResourceRegistry = $imageResourceRegistry;
        $this->workflowRegistry = $workflowRegistry;
        $this->timeoutThreshold = $timeoutThreshold;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->imageResourceRegistry->isEmpty()) {
            $this->io->writeln(sprintf('No resources implements the interface %s', ImageInterface::class));

            return 0;
        }

        $dtThreshold = new \DateTimeImmutable(sprintf('-%d min', $this->timeoutThreshold));
        $this->io->note(sprintf(
            'Will timeout images that\'s been processing longer than %d minutes (i.e. processingStateUpdatedAt <= %s)',
            $this->timeoutThreshold,
            $dtThreshold->format('Y-m-d H:i:s')
        ));

        $this->io->section('Processing image resources');

        foreach ($this->imageResourceRegistry->all() as $imageResource) {
            $this->processResource($imageResource);
        }

        return 0;
    }

    private function processResource(ImageResource $imageResource): void
    {
        $this->io->text(sprintf('- %s', $imageResource->class));

        $manager = $this->getManager($imageResource->class);

        /** @var ObjectRepository|EntityRepository $repository */
        $repository = $manager->getRepository($imageResource->class);
        Assert::isInstanceOf($repository, EntityRepository::class);

        $workflow = null;

        try {
            $failedCount = 0;
            $i = 0;
            foreach ($this->getImages($repository, $manager) as $images) {
                foreach ($images as $image) {
                    if ($workflow === null) {
                        $workflow = $this->workflowRegistry->get($image, ProcessWorkflow::NAME);
                    }

                    if ($workflow->can($image, ProcessWorkflow::TRANSITION_FAIL)) {
                        $workflow->apply($image, ProcessWorkflow::TRANSITION_FAIL);
                    } else {
                        ++$failedCount;
                    }

                    ++$i;
                }

                $manager->flush();
            }
        } finally {
            $manager->flush();
        }

        if ($failedCount > 0) {
            $this->io->warning(sprintf('%s images could NOT take the \'%s\' transition', $failedCount, ProcessWorkflow::TRANSITION_FAIL));
        }

        $this->io->success(sprintf('%d image%s was timed out...', $i, $i === 1 ? '' : 's'));
    }

    /**
     * @psalm-suppress MixedReturnTypeCoercion
     *
     * @return \Generator<int, ImageInterface[]>
     */
    private function getImages(EntityRepository $repository, ObjectManager $manager, int $resultsPerPage = 50): \Generator
    {
        $qb = $repository->createQueryBuilder('o');
        $qb
            ->andWhere('o.processingState IN (:processingStates)')
            ->andWhere('o.processingStateUpdatedAt <= :threshold')
            ->setParameter('processingStates', [ImageInterface::PROCESSING_STATE_PENDING, ImageInterface::PROCESSING_STATE_PROCESSING])
            ->setParameter('threshold', new \DateTimeImmutable(sprintf('-%d min', $this->timeoutThreshold)))
            ->setMaxResults($resultsPerPage)
        ;

        do {
            $images = $qb->getQuery()->getResult();
            Assert::isArray($images);

            yield $images;

            $manager->clear();
        } while ([] !== $images);
    }
}
