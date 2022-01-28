<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Message\Command;

use Setono\SyliusImagePlugin\Model\ImageInterface;

final class ProcessImage implements CommandInterface
{
    /** @var class-string */
    public string $class;

    /** @var mixed */
    public $imageId;

    /**
     * @param class-string $class
     * @param mixed $imageId
     */
    public function __construct(string $class, $imageId)
    {
        $this->class = $class;
        $this->imageId = $imageId;
    }

    public static function fromImage(ImageInterface $image): self
    {
        return new self(get_class($image), $image->getId());
    }
}
