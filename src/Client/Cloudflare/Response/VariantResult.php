<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Client\Cloudflare\Response;

use Setono\SyliusImagePlugin\Client\Cloudflare\DTO\VariantOptions;
use Spatie\DataTransferObject\FlexibleDataTransferObject;

final class VariantResult extends FlexibleDataTransferObject
{
    public string $id;

    /**
     * When you create a variant you only get the id in the response
     */
    public ?VariantOptions $options = null;

    public ?bool $neverRequireSignedURLs = null;
}
