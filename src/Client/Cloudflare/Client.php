<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Client\Cloudflare;

use Setono\SyliusImagePlugin\Client\Cloudflare\Response\UploadImageResponse;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class Client implements ClientInterface
{
    private HttpClientInterface $httpClient;

    private string $accountIdentifier;

    public function __construct(HttpClientInterface $httpClient, string $accountIdentifier)
    {
        $this->httpClient = $httpClient;
        $this->accountIdentifier = $accountIdentifier;
    }

    public function uploadImage(string $filename, array $metadata = []): UploadImageResponse
    {
        $formData = new FormDataPart([
            'file' => DataPart::fromPath($filename),
        ]);

        return new UploadImageResponse(
            $this->httpClient->request(
                'POST',
                sprintf('/client/v4/accounts/%s/images/v1', $this->accountIdentifier),
                [
                    'headers' => $formData->getPreparedHeaders()->toArray(),
                    'body' => $formData->bodyToIterable(),
                ]
            )->toArray()
        );
    }
}
