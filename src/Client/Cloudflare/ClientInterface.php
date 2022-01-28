<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Client\Cloudflare;

use Setono\SyliusImagePlugin\Client\Cloudflare\Response\UploadImageResponse;

interface ClientInterface
{
    public function uploadImage(string $filename, array $metadata = []): UploadImageResponse;
}
