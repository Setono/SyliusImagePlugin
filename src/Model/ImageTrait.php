<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Model;

use Doctrine\ORM\Mapping as ORM;

trait ImageTrait
{
    /** @ORM\Column(type="boolean", options={"default": false}) */
    protected bool $processed = false;

    public function isProcessed(): bool
    {
        return $this->processed;
    }

    public function setProcessed(bool $processed = true): void
    {
        $this->processed = $processed;
    }
}
