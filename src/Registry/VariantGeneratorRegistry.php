<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Registry;

use Setono\SyliusImagePlugin\VariantGenerator\VariantGeneratorInterface;

/**
 * @implements \IteratorAggregate<string, VariantGeneratorInterface>
 */
final class VariantGeneratorRegistry implements VariantGeneratorRegistryInterface, \IteratorAggregate
{
    /** @var array<string, VariantGeneratorInterface> */
    private array $variantGenerators = [];

    public function add(VariantGeneratorInterface $variantGenerator): void
    {
        if ($this->has($variantGenerator)) {
            return;
        }

        $this->variantGenerators[$variantGenerator->getName()] = $variantGenerator;
    }

    public function get(string $variantGenerator): VariantGeneratorInterface
    {
        if (!$this->has($variantGenerator)) {
            throw new \InvalidArgumentException(sprintf(
                'The variant generator "%s" is not registered',
                $variantGenerator
            ));
        }

        return $this->variantGenerators[$variantGenerator];
    }

    /**
     * @psalm-assert-if-true VariantGeneratorInterface $this->variantGenerators[$variantGenerator]
     */
    public function has($variantGenerator): bool
    {
        if ($variantGenerator instanceof VariantGeneratorInterface) {
            $variantGenerator = $variantGenerator->getName();
        }

        return isset($this->variantGenerators[$variantGenerator]);
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->variantGenerators);
    }
}
