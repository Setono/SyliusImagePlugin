<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Client\Cloudflare\Response;

final class ImageVariant
{
    public string $name;

    public string $url;

    private function __construct(string $name, string $url)
    {
        $this->name = $name;
        $this->url = $url;
    }

    public static function fromUrl(string $url): self
    {
        // the last part of the Cloudflare url is the name of the variant
        return new self(\basename($url), $url);
    }
}
