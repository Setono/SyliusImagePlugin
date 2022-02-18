<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Workflow;

use Setono\SyliusImagePlugin\Model\ImageInterface;
use Symfony\Component\Workflow\DefinitionBuilder;
use Symfony\Component\Workflow\MarkingStore\MethodMarkingStore;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\Workflow;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class ProcessWorkflow
{
    public const NAME = 'setono_sylius_image__process';

    public const TRANSITION_START = 'process';

    public const TRANSITION_FINISH = 'finish';

    public const TRANSITION_FAIL = 'fail';

    private function __construct()
    {
    }

    /**
     * @return array<array-key, string>
     */
    public static function getStates(): array
    {
        return [
            ImageInterface::PROCESSING_STATE_PENDING,
            ImageInterface::PROCESSING_STATE_PROCESSING,
            ImageInterface::PROCESSING_STATE_PROCESSED,
            ImageInterface::PROCESSING_STATE_FAILED,
        ];
    }

    public static function getConfig(): array
    {
        $transitions = [];
        foreach (self::getTransitions() as $transition) {
            $transitions[$transition->getName()] = [
                'from' => $transition->getFroms(),
                'to' => $transition->getTos(),
            ];
        }

        return [
            self::NAME => [
                'type' => 'state_machine',
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'processingState',
                ],
                'supports' => ImageInterface::class,
                'initial_marking' => ImageInterface::PROCESSING_STATE_PENDING,
                'places' => self::getStates(),
                'transitions' => $transitions,
            ],
        ];
    }

    public static function getWorkflow(EventDispatcherInterface $eventDispatcher): Workflow
    {
        $definitionBuilder = new DefinitionBuilder(self::getStates(), self::getTransitions());

        return new Workflow(
            $definitionBuilder->build(),
            new MethodMarkingStore(true, 'state'),
            $eventDispatcher,
            self::NAME
        );
    }

    /**
     * @return array<array-key, Transition>
     */
    public static function getTransitions(): array
    {
        return [
            new Transition(self::TRANSITION_START, [ImageInterface::PROCESSING_STATE_PENDING, ImageInterface::PROCESSING_STATE_FAILED], ImageInterface::PROCESSING_STATE_PROCESSING),
            new Transition(self::TRANSITION_FINISH, ImageInterface::PROCESSING_STATE_PROCESSING, ImageInterface::PROCESSING_STATE_PROCESSED),
            new Transition(self::TRANSITION_FAIL, ImageInterface::PROCESSING_STATE_PROCESSING, ImageInterface::PROCESSING_STATE_FAILED),
        ];
    }
}
