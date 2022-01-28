<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Config;

use Setono\SyliusImagePlugin\VariantGenerator\VariantGeneratorInterface;

final class VariantCollection implements VariantCollectionInterface, \IteratorAggregate
{
    /** @var array<string, Variant> */
    private array $variants = [];

    public function add(Variant $variant): void
    {
        if ($this->has($variant)) {
            return;
        }

        $this->variants[$variant->name] = $variant;
    }

    /**
     * @psalm-assert-if-true Variant $this->variants[$variant]
     */
    public function has($variant): bool
    {
        if ($variant instanceof Variant) {
            $variant = $variant->name;
        }

        return isset($this->variants[$variant]);
    }

    public function hasOneWithGenerator(string $generator): bool
    {
        foreach ($this->variants as $variant) {
            if ($variant->generator === $generator) {
                return true;
            }
        }

        return false;
    }

    public function getByGenerator($generator): array
    {
        if ($generator instanceof VariantGeneratorInterface) {
            $generator = $generator->getName();
        }

        return array_filter($this->variants, static function (Variant $variant) use ($generator): bool {
            return $generator === $variant->generator;
        });
    }

    public function isEmpty(): bool
    {
        return [] === $this->variants;
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->variants);
    }
}
