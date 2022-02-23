<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Client\Cloudflare\Response;

use Spatie\DataTransferObject\FlexibleDataTransferObject;

final class VariantDetailsResult extends FlexibleDataTransferObject
{
    public VariantResult $variant;
}
