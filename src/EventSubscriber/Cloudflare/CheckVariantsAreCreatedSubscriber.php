<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\EventSubscriber\Cloudflare;

use Setono\SyliusImagePlugin\Config\VariantCollectionInterface;
use Setono\SyliusImagePlugin\Event\ProcessingStartedEvent;
use Setono\SyliusImagePlugin\VariantGenerator\CloudflareVariantGenerator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class CheckVariantsAreCreatedSubscriber implements EventSubscriberInterface
{
    private VariantCollectionInterface $variantCollection;

    public function __construct(VariantCollectionInterface $variantCollection)
    {
        $this->variantCollection = $variantCollection;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProcessingStartedEvent::class => 'check',
        ];
    }

    public function check(ProcessingStartedEvent $event): void
    {
        if (!$this->variantCollection->hasOneWithGenerator(CloudflareVariantGenerator::NAME)) {
            return;
        }

        // todo check that all variants are created on Cloudflare
    }
}
