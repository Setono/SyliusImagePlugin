<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Exception;

use RuntimeException;
use Setono\SyliusImagePlugin\Message\Command\ProcessImage;
use Symfony\Component\Messenger\Exception\UnrecoverableExceptionInterface;

final class ImageProcessingFailedException extends RuntimeException implements UnrecoverableExceptionInterface
{
    public static function fromCommand(ProcessImage $command, \Throwable $previous): self
    {
        return new self(sprintf(
            'Processing of entity %s (id: %s) failed with error: %s',
            $command->class,
            (string) $command->imageId,
            $previous->getMessage()
        ), 0, $previous);
    }
}
