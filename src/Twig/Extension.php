<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class Extension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('ssi_image_path', [Runtime::class, 'imagePath']),
        ];
    }
}
