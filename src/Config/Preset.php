<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Config;

use Webmozart\Assert\Assert;

final class Preset
{
    public const FORMAT_AVIF = 'avif';

    public const FORMAT_JPG = 'jpg';

    public const FORMAT_WEBP = 'webp';

    /**
     * TODO Add this to the docs of the plugin
     *
     * See descriptions of different fits here: https://developers.cloudflare.com/images/cloudflare-images/resize-images
     */

    /**
     * Image will be shrunk and cropped to fit within the area specified by width and height.
     * The image will not be enlarged. For images smaller than the given dimensions it is the same as scale-down.
     * For images larger than the given dimensions, it is the same as cover.
     */
    public const FIT_CROP = 'crop';

    /**
     * Image will be shrunk in size to fully fit within the given width or height, but will not be enlarged
     */
    public const FIT_SCALE_DOWN = 'scale-down';

    /**
     * Image will be resized (shrunk or enlarged) to be as large as possible within the given width or height
     * while preserving the aspect ratio, and the extra area will be filled with a background color (white by default).
     */
    public const FIT_PAD = 'pad';

    /**
     * Image will be resized (shrunk or enlarged) to be as large as possible
     * within the given width or height while preserving the aspect ratio.
     */
    public const FIT_CONTAIN = 'contain';

    /**
     * Image will be resized to exactly fill the entire area specified
     * by width and height, and will be cropped if necessary.
     */
    public const FIT_COVER = 'cover';

    public const AVAILABLE_FITS = [
        self::FIT_CROP,
        self::FIT_SCALE_DOWN,
        self::FIT_PAD,
        self::FIT_CONTAIN,
        self::FIT_COVER,
    ];

    /**
     * The name of the variant. Right now it will be the name of the filter set
     */
    public string $name;

    /** @var list<string> */
    public array $formats;

    public ?int $width;

    public ?int $height;

    public string $fit = self::FIT_SCALE_DOWN;

    public ?string $generator = null;

    /**
     * @param list<string> $formats
     */
    public function __construct(string $name, array $formats, int $width = null, int $height = null)
    {
        Assert::stringNotEmpty($name);
        Assert::nullOrGreaterThan($width, 0);
        Assert::nullOrGreaterThan($height, 0);
        if (null === $width && null === $height) {
            throw new \InvalidArgumentException('Either the width or height must be set on a preset.');
        }
        Assert::notEmpty($formats);

        $this->name = $name;
        $this->formats = $formats;
        $this->width = $width;
        $this->height = $height;
    }

    /**
     * @param array{name: string, formats: list<string>, width?: int, height?: int, fit?: string, generator?: string} $configuration
     */
    public static function fromArray(array $configuration): self
    {
        $obj = new self(
            $configuration['name'],
            $configuration['formats'],
            $configuration['width'] ?? null,
            $configuration['height'] ?? null
        );

        if (isset($configuration['fit'])) {
            Assert::oneOf($configuration['fit'], self::AVAILABLE_FITS);
            $obj->fit = $configuration['fit'];
        }

        $obj->generator = $configuration['generator'] ?? null;

        return $obj;
    }

    public function equals(self $other): bool
    {
        return $this->width === $other->width
            && $this->height === $other->height
            && $this->fit === $other->fit
        ;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
