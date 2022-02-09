<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\EventSubscriber;

use Setono\SyliusImagePlugin\Model\ImageInterface;
use Setono\SyliusImagePlugin\Workflow\ProcessWorkflow;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;
use Webmozart\Assert\Assert;

final class UpdateRetryAtSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        $event = sprintf('workflow.%s.transition.%s', ProcessWorkflow::NAME, ProcessWorkflow::TRANSITION_FAIL);

        return [
            $event => 'update',
        ];
    }

    public function update(Event $event): void
    {
        /** @var ImageInterface|object $image */
        $image = $event->getSubject();
        Assert::isInstanceOf($image, ImageInterface::class);

        $image->setProcessingRetryAt((new \DateTimeImmutable())->add(new \DateInterval(sprintf('PT%dM', $this->delay($image->getProcessingTries())))));
    }

    /**
     * Returns the number of minutes to wait until next retry
     *
     * Credit goes to: https://github.com/EventSaucePHP/BackOff/blob/main/src/FibonacciBackOffStrategy.php
     */
    private function delay(int $tries): int
    {
        $phi = 1.6180339887499; // (1 + sqrt(5)) / 2;

        return (int) (($phi ** $tries - (1 - $phi) ** $tries) / sqrt(5));
    }
}
