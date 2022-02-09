<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Config;

use Setono\SyliusImagePlugin\VariantGenerator\VariantGeneratorInterface;

/**
 * A VariantCollectionInterface MUST be serializable
 */
interface VariantCollectionInterface extends \Traversable
{
    public function add(Variant $variant): void;

    public function get(string $variant): Variant;

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

    /**
     * Returns true if the diff between this and $other is empty
     */
    public function equals(self $other): bool;

    /**
     * Compares two VariantCollectionInterfaces and returns a new VariantCollectionInterface with the variants that are
     * - in $other, but not in this
     * - in $other and this, but aren't equal
     *
     * This implies that if you removed variants from $other this implementation would not consider that a diff
     */
    public function diff(self $other): self;

    /**
     * Returns all variants indexed by name
     *
     * @return array<string, Variant>
     */
    public function toArray(): array;
}
