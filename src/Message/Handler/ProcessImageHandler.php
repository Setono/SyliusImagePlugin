<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Message\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Setono\DoctrineObjectManagerTrait\ORM\ORMManagerTrait;
use Setono\SyliusImagePlugin\Message\Command\ProcessImage;
use Setono\SyliusImagePlugin\Model\ImageInterface;
use Setono\SyliusImagePlugin\Processor\ProcessorInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Webmozart\Assert\Assert;

final class ProcessImageHandler implements MessageHandlerInterface
{
    use ORMManagerTrait;

    private ProcessorInterface $processor;

    public function __construct(ManagerRegistry $managerRegistry, ProcessorInterface $processor)
    {
        $this->managerRegistry = $managerRegistry;
        $this->processor = $processor;
    }

    public function __invoke(ProcessImage $message): void
    {
        $manager = $this->getManager($message->class);

        $image = $manager->find($message->class, $message->imageId);
        if(null === $image) {
            return;
        }
        Assert::isInstanceOf($image, ImageInterface::class);

        $this->processor->process($image);

        $manager->flush();
    }
}
