<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Config;

use Setono\SyliusImagePlugin\Model\ImageInterface;

final class ImageResource
{
    /**
     * @readonly
     *
     * @var string The Sylius resource name, i.e. `sylius.product_image`
     */
    public string $name;

    /**
     * @readonly
     *
     * @var class-string<ImageInterface> The FQN class name of the class representing this resource
     */
    public string $class;

    /**
     * @readonly
     *
     * @var list<Preset> A list of presets that should be generated for this resource
     */
    public array $presets;

    /**
     * @param class-string<ImageInterface> $class
     * @param list<Preset> $presets
     */
    public function __construct(string $name, string $class, array $presets = [])
    {
        $this->name = $name;
        $this->class = $class;
        $this->presets = $presets;
    }

    /**
     * @param array{name: string, class: class-string<ImageInterface>, presets: list<Preset>} $imageResource
     */
    public static function fromArray(array $imageResource): self
    {
        return new self($imageResource['name'], $imageResource['class'], $imageResource['presets']);
    }
}
