<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\ImageGenerator;

final class ImageGeneratorRegistry implements ImageGeneratorRegistryInterface
{
    /** @var array<string, ImageGeneratorInterface> */
    private array $generators = [];

    private string $defaultImageGeneratorId;

    public function __construct(string $defaultImageGeneratorId)
    {
        $this->defaultImageGeneratorId = $defaultImageGeneratorId;
    }

    public function all(): array
    {
        return $this->generators;
    }

    public function add(string $name, ImageGeneratorInterface $imageGenerator): void
    {
        if ($this->has($name)) {
            return;
        }

        $this->generators[$name] = $imageGenerator;
    }

    public function get(string $imageGenerator): ImageGeneratorInterface
    {
        if (!$this->has($imageGenerator)) {
            throw new \InvalidArgumentException(sprintf(
                'The variant generator "%s" is not registered',
                $imageGenerator
            ));
        }

        return $this->generators[$imageGenerator];
    }

    public function has(string $imageGenerator): bool
    {
        return isset($this->generators[$imageGenerator]);
    }

    public function getDefault(): ImageGeneratorInterface
    {
        return $this->get($this->defaultImageGeneratorId);
    }
}
