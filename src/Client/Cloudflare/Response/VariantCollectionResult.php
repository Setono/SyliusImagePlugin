<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Client\Cloudflare\Response;

use Spatie\DataTransferObject\FlexibleDataTransferObject;

final class VariantCollectionResult extends FlexibleDataTransferObject
{
    /** @var \Setono\SyliusImagePlugin\Client\Cloudflare\Response\VariantResult[] */
    public array $variants;

    public function __construct(array $parameters = [])
    {
        if (isset($parameters['variants']) && is_array($parameters['variants'])) {
            $parameters['variants'] = array_values($parameters['variants']);
        }

        parent::__construct($parameters);
    }
}
