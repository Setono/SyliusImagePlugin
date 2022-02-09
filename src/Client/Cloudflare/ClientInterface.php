<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Client\Cloudflare;

use Setono\SyliusImagePlugin\Client\Cloudflare\Response\ImageResponse;

interface ClientInterface
{
    public function uploadImage(string $filename, array $metadata = []): ImageResponse;

    public function getImageDetails(string $identifier): ImageResponse;
}
