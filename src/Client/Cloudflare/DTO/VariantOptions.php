<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Client\Cloudflare\DTO;

use Spatie\DataTransferObject\FlexibleDataTransferObject;
use Webmozart\Assert\Assert;

final class VariantOptions extends FlexibleDataTransferObject
{
    public const FIT_CONTAIN = 'contain';

    public const FIT_COVER = 'cover';

    public const FIT_CROP = 'crop';

    public const FIT_PAD = 'pad';

    public const FIT_SCALE_DOWN = 'scale-down';

    public const METADATA_COPYRIGHT = 'copyright';

    public const METADATA_KEEP = 'keep';

    public const METADATA_NONE = 'none';

    public string $fit;

    public string $metadata = self::METADATA_NONE;

    public int $width;

    public int $height;

    public function __construct(array $parameters = [])
    {
        parent::__construct($parameters);

        Assert::oneOf($this->fit, self::getFits());
        Assert::oneOf($this->metadata, self::getMetadatas());
    }

    /**
     * @return array<array-key, string>
     */
    public static function getFits(): array
    {
        return [
            self::FIT_CONTAIN,
            self::FIT_COVER,
            self::FIT_CROP,
            self::FIT_PAD,
            self::FIT_SCALE_DOWN,
        ];
    }

    /**
     * @return array<array-key, string>
     */
    public static function getMetadatas(): array
    {
        return [
            self::METADATA_COPYRIGHT,
            self::METADATA_KEEP,
            self::METADATA_NONE,
        ];
    }
}
