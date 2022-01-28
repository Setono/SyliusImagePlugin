<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Config;

use Webmozart\Assert\Assert;

final class Variant
{
    /**
     * See descriptions of different fits here: https://developers.cloudflare.com/images/cloudflare-images/resize-images
     */
    public const FIT_CROP = 'crop';

    public const FIT_SCALE_DOWN = 'scale_down';

    /**
     * The name of the variant. Right now it will be the name of the filter set
     */
    public string $name;

    public ?int $width;

    public ?int $height;

    public string $fit;

    /**
     * The name of the generator to use for this variant, i.e. 'cloudflare'
     */
    public string $generator;

    public function __construct(string $name, string $generator, ?int $width, ?int $height, string $fit = self::FIT_CROP)
    {
        Assert::nullOrGreaterThan($width, 0);
        Assert::nullOrGreaterThan($height, 0);
        if (null === $width && null === $height) {
            throw new \InvalidArgumentException('Both the width and height cannot be null');
        }

        $this->name = $name;
        $this->generator = $generator;
        $this->width = $width;
        $this->height = $height;
        $this->fit = $fit;
    }

    public static function fromFilterSet(string $name, string $generator, array $filterSet): self
    {
        if (!isset($filterSet['filters']['thumbnail']['size'])) {
            throw new \InvalidArgumentException(sprintf('Right now this plugin only supports the thumbnail filter for LiipImagineBundle filters. The filter set "%s" does not have this filter', $name));
        }

        /** @psalm-suppress MixedArrayAccess */
        $size = $filterSet['filters']['thumbnail']['size'];
        Assert::isArray($size);

        $width = $size[0] ?? null;
        $height = $size[1] ?? null;

        Assert::nullOrInteger($width);
        Assert::nullOrInteger($height);

        return new self($name, $generator, $width, $height, self::FIT_CROP);
    }
}
