<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Event;

use Setono\SyliusImagePlugin\Config\VariantCollectionInterface;

final class ProcessingStartedEvent
{
    /** @psalm-readonly */
    public VariantCollectionInterface $variantCollection;

    public function __construct(VariantCollectionInterface $variantCollection)
    {
        $this->variantCollection = $variantCollection;
    }
}
