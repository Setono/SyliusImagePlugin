<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Controller\Action;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * This controller is only hit when the image hasn't been preloaded yet, and we handle this case
 * by redirecting to the original image
 */
final class RedirectNonExistingImageVariantAction
{
    public function __invoke(Request $request, string $variant, string $path): RedirectResponse
    {
        $response = new RedirectResponse('/media/image/' . $path);
        // todo add cache control headers

        return $response;
    }
}
