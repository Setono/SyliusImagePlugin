<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Image;

final class Image
{
    public string $source;

    public string $alt = '';

    public function __construct(string $source)
    {
        $this->source = $source;
    }

    public function asHtml(): string
    {
        return sprintf('<img src="%s" alt="%s">', $this->source, $this->alt);
    }

    public function __toString(): string
    {
        return $this->asHtml();
    }
}
