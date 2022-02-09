<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Client\Cloudflare\Response;

use Spatie\DataTransferObject\FlexibleDataTransferObject;

abstract class BaseResponse extends FlexibleDataTransferObject
{
    public bool $success;

    public array $errors = [];

    public array $messages = [];
}
