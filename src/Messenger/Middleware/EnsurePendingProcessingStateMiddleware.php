<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Messenger\Middleware;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\ManagerRegistry;
use Setono\DoctrineObjectManagerTrait\ORM\ORMManagerTrait;
use Setono\SyliusImagePlugin\Message\Command\ProcessImage;
use Setono\SyliusImagePlugin\Model\ImageInterface;
use Setono\SyliusImagePlugin\Workflow\ProcessWorkflow;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Workflow\Registry;
use Webmozart\Assert\Assert;

final class EnsurePendingProcessingStateMiddleware implements MiddlewareInterface
{
    use ORMManagerTrait;

    private Registry $workflowRegistry;

    public function __construct(ManagerRegistry $managerRegistry, Registry $workflowRegistry)
    {
        $this->managerRegistry = $managerRegistry;
        $this->workflowRegistry = $workflowRegistry;
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();

        // If the message already has an PendingProcessingStateEnsuredStamp we don't need to prepare/check the image
        // This is the case when the message is received by the handler.
        // Middleware handles the message both when sending and receiving.
        // See https://symfony.com/doc/current/messenger.html#middleware
        if ($message instanceof ProcessImage && null === $envelope->last(PendingProcessingStateEnsuredStamp::class)) {
            $manager = $this->getManager($message->class);

            $image = $manager->find($message->class, $message->imageId);
            if ($image === null) {
                // Something is clearly wrong. Don't pass the message along.
                return $envelope;
            }

            Assert::isInstanceOf($image, ImageInterface::class);

            $workflow = $this->workflowRegistry->get($image, ProcessWorkflow::NAME);

            if (!$workflow->can($image, ProcessWorkflow::TRANSITION_ENQUEUE)) {
                // Don't pass it down the line
                return $envelope;
            }

            $workflow->apply($image, ProcessWorkflow::TRANSITION_ENQUEUE);

            try {
                $manager->flush();
            } catch (OptimisticLockException $ex) {
                // Don't pass it down the line
                return $envelope;
            }

            $envelope = $envelope->with(new PendingProcessingStateEnsuredStamp());
        }

        return $stack->next()->handle($envelope, $stack);
    }
}
