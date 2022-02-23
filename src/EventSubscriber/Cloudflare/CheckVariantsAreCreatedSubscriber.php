<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\EventSubscriber\Cloudflare;

use Setono\SyliusImagePlugin\Client\Cloudflare\ClientInterface;
use Setono\SyliusImagePlugin\Client\Cloudflare\Response\VariantResult;
use Setono\SyliusImagePlugin\Config\Variant;
use Setono\SyliusImagePlugin\Event\ProcessingStartedEvent;
use Setono\SyliusImagePlugin\VariantGenerator\CloudflareVariantGenerator;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class CheckVariantsAreCreatedSubscriber implements EventSubscriberInterface
{
    private ClientInterface $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProcessingStartedEvent::class => 'check',
        ];
    }

    public function check(ProcessingStartedEvent $event): void
    {
        if (!$event->variantCollection->hasOneWithGenerator(CloudflareVariantGenerator::NAME)) {
            return;
        }

        $existingVariants = array_map(static function (VariantResult $variantResult) {
            return Container::underscore($variantResult->id); // variants in Cloudflare are saved as camel case
        }, $this->client->getVariants()->result->variants);

        /** @var array<array-key, Variant> $variantsToCreate */
        $variantsToCreate = [];

        foreach ($event->variantCollection->getByGenerator(CloudflareVariantGenerator::NAME) as $variant) {
            if (in_array($variant->name, $existingVariants, true)) {
                continue;
            }

            $variantsToCreate[] = $variant;
        }

        foreach ($variantsToCreate as $item) {
            $this->client->createVariant($item->name, [
                'fit' => $item->fit,
                'width' => $item->width,
                'height' => $item->height,
            ]);
        }
    }
}
