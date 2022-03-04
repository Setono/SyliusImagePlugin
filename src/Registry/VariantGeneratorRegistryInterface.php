<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Registry;

use Setono\SyliusImagePlugin\VariantGenerator\VariantGeneratorInterface;

interface VariantGeneratorRegistryInterface extends \Traversable
{
    public function add(VariantGeneratorInterface $variantGenerator): void;

    public function get(string $variantGenerator): VariantGeneratorInterface;

    /**
     * @param VariantGeneratorInterface|string $variantGenerator
     */
    public function has($variantGenerator): bool;
}
