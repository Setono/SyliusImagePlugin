<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\EventSubscriber;

use Setono\SyliusImagePlugin\Model\ImageInterface;
use Setono\SyliusImagePlugin\Workflow\ProcessWorkflow;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;
use Webmozart\Assert\Assert;

final class ResetProcessingRetryStatusSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        $event = sprintf('workflow.%s.completed.%s', ProcessWorkflow::NAME, ProcessWorkflow::TRANSITION_FINISH);

        return [
            $event => 'resetProcessingRetryStatus',
        ];
    }

    public function resetProcessingRetryStatus(Event $event): void
    {
        /** @var ImageInterface|object $image */
        $image = $event->getSubject();
        Assert::isInstanceOf($image, ImageInterface::class);

        $image->resetProcessingTries();
        $image->setProcessingRetryAt(null);
    }
}
