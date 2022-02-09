<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\EventSubscriber;

use Setono\SyliusImagePlugin\Model\ImageInterface;
use Setono\SyliusImagePlugin\Workflow\ProcessWorkflow;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;
use Webmozart\Assert\Assert;

final class IncrementTriesSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        $event = sprintf('workflow.%s.transition.%s', ProcessWorkflow::NAME, ProcessWorkflow::TRANSITION_START);

        return [
            $event => 'increment',
        ];
    }

    public function increment(Event $event): void
    {
        /** @var ImageInterface|object $image */
        $image = $event->getSubject();
        Assert::isInstanceOf($image, ImageInterface::class);

        $image->incrementProcessingTries();
    }
}
