<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Client\Cloudflare\Response;

use Spatie\DataTransferObject\FlexibleDataTransferObject;

final class UploadImageResponse extends FlexibleDataTransferObject
{
    public bool $success;

    public array $errors = [];

    public array $messages = [];

    public UploadImageResponseResult $result;
}
