<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Config;

use Webmozart\Assert\Assert;

final class Variant
{
    /**
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

    public ?int $width;

    public ?int $height;

    public ?string $fit;

    /**
     * The name of the generator to use for this variant, i.e. 'cloudflare'
     */
    public string $generator;

    public function __construct(string $name, string $generator, ?int $width, ?int $height, ?string $fit)
    {
        Assert::stringNotEmpty($name);
        Assert::nullOrGreaterThan($width, 0);
        Assert::nullOrGreaterThan($height, 0);
        Assert::nullOrStringNotEmpty($fit);

        $this->name = $name;
        $this->generator = $generator;
        $this->width = $width;
        $this->height = $height;
        $this->fit = $fit;
    }

    public static function fromFilterSet(string $name, string $generator, array $filterSet): self
    {
        $filters = $filterSet['filters'] ?? [];

        return new self($name, $generator, $filters['thumbnail']['size'][0] ?? null, $filters['thumbnail']['size'][1] ?? null, self::FIT_SCALE_DOWN);
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

    /**
     * @psalm-assert array{filters: array{thumbnail: array{size: list<int>}}} $filterSet
     */
    private static function validateFilterSet(string $name, array $filterSet): void
    {
        Assert::keyExists($filterSet, 'filters');

        $filters = $filterSet['filters'];
        Assert::isArray($filters);

        Assert::keyExists($filters, 'thumbnail', sprintf('A thumbnail filter must be configured for the filter set "%s"', $name));

        $filterValidators = [
            'thumbnail' => static function (array $options): void {
                Assert::keyExists($options, 'size', 'The thumbnail filter must have a size option');
                $size = $options['size'];
                Assert::isList($size);
                Assert::count($size, 2);
                Assert::allInteger($size);
            },
        ];

        foreach ($filters as $filter => $options) {
            Assert::oneOf($filter, ['thumbnail', 'background'], sprintf('The filter "%s" on the "%s" filter set is not supported', $name, $filter));

            if (isset($filterValidators[$filter])) {
                $filterValidators[$filter]($options);
            }
        }
    }
}
