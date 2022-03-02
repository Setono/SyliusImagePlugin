<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\EventListener\Doctrine;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Setono\SyliusImagePlugin\Message\Command\ProcessImage;
use Setono\SyliusImagePlugin\Model\ImageInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class ProcessUpdatedImageListener
{
    private MessageBusInterface $commandBus;

    public function __construct(MessageBusInterface $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function postPersist(LifecycleEventArgs $eventArgs): void
    {
        $obj = $eventArgs->getObject();
        if (!$obj instanceof ImageInterface) {
            return;
        }

        $this->process($obj);
    }

    public function postUpdate(LifecycleEventArgs $eventArgs): void
    {
        $obj = $eventArgs->getObject();
        if (!$obj instanceof ImageInterface) {
            return;
        }

        // When images are uploaded they are temporarily held inside the file property of an image.
        // At least this is the default way Sylius handles it, so this is the only way we can easily
        // check if an image file is updated
        if (!$obj->hasFile()) {
            return;
        }

        $this->process($obj);
    }

    private function process(ImageInterface $image): void
    {
        $this->commandBus->dispatch(ProcessImage::fromImage($image));
    }
}
