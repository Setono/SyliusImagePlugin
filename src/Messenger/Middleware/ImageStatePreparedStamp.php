<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Messenger\Middleware;

use Symfony\Component\Messenger\Stamp\StampInterface;

final class ImageStatePreparedStamp implements StampInterface
{
    private \DateTimeImmutable $preparedAt;

    public function __construct()
    {
        $this->preparedAt = new \DateTimeImmutable();
    }

    public function getPreparedAt(): \DateTimeImmutable
    {
        return $this->preparedAt;
    }
}
