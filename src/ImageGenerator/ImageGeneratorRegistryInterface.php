<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\ImageGenerator;

interface ImageGeneratorRegistryInterface
{
    /**
     * @return array<string, ImageGeneratorInterface>
     */
    public function all(): array;

    public function add(string $name, ImageGeneratorInterface $imageGenerator): void;

    /**
     * @throws \InvalidArgumentException if the image generator does not exist
     */
    public function get(string $imageGenerator): ImageGeneratorInterface;

    public function has(string $imageGenerator): bool;

    /**
     * Returns the default image generator
     */
    public function getDefault(): ImageGeneratorInterface;
}
