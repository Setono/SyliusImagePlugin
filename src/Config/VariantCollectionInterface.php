<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Config;

use Setono\SyliusImagePlugin\VariantGenerator\VariantGeneratorInterface;

interface VariantCollectionInterface extends \Traversable
{
    public function add(Variant $variant): void;

    /**
     * @param string|Variant $variant
     */
    public function has($variant): bool;

    /**
     * Returns true if the variant collection has at least one variant where the generator equals $generator
     */
    public function hasOneWithGenerator(string $generator): bool;

    /**
     * @param string|VariantGeneratorInterface $generator
     *
     * @return array<string, Variant>
     */
    public function getByGenerator($generator): array;

    public function isEmpty(): bool;
}
