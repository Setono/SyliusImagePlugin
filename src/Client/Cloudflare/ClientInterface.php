<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Client\Cloudflare;

use Setono\SyliusImagePlugin\Client\Cloudflare\DTO\VariantOptions;
use Setono\SyliusImagePlugin\Client\Cloudflare\Response\ImageResponse;
use Setono\SyliusImagePlugin\Client\Cloudflare\Response\VariantCollectionResponse;
use Setono\SyliusImagePlugin\Client\Cloudflare\Response\VariantDetailsResponse;
use Setono\SyliusImagePlugin\Client\Cloudflare\Response\VariantResponse;

interface ClientInterface
{
    public function uploadImage(string $filename, array $metadata = []): ImageResponse;

    public function deleteImage(string $identifier): void;

    public function getImageDetails(string $identifier): ImageResponse;

    public function getVariant(string $identifier): VariantDetailsResponse;

    public function getVariants(): VariantCollectionResponse;

    /**
     * @param VariantOptions|array $options
     */
    public function createVariant(string $name, $options, bool $neverRequireSignedUrls = false): VariantResponse;
}
