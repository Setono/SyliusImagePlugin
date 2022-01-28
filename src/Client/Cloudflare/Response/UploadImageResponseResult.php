<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Client\Cloudflare\Response;

use Spatie\DataTransferObject\FlexibleDataTransferObject;

final class UploadImageResponseResult extends FlexibleDataTransferObject
{
    public string $id;

    public string $filename;

    public ?array $metadata;

    public bool $requireSignedURLs;

    public array $variants;

    public string $uploaded; // todo convert to DateTimeImmutable
}
