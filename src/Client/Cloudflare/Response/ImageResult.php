<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Client\Cloudflare\Response;

use Spatie\DataTransferObject\FlexibleDataTransferObject;
use Webmozart\Assert\Assert;

final class ImageResult extends FlexibleDataTransferObject
{
    public string $id;

    public string $filename;

    public ?array $metadata;

    public bool $requireSignedURLs;

    /** @var \Setono\SyliusImagePlugin\Client\Cloudflare\Response\ImageVariant[] */
    public array $variants;

    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(array $parameters = [])
    {
        Assert::keyExists($parameters, 'variants');
        Assert::isArray($parameters['variants']);

        $variants = [];
        foreach ($parameters['variants'] as $url) {
            Assert::string($url);
            $variants[] = ImageVariant::fromUrl($url);
        }
        $parameters['variants'] = $variants;

        parent::__construct($parameters);
    }
}
