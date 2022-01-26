<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class Extension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('ssi_image', [Runtime::class, 'image']),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('ssi_image', [Runtime::class, 'image']),
        ];
    }
}
