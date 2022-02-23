<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Client\Cloudflare;

use Setono\SyliusImagePlugin\Client\Cloudflare\DTO\VariantOptions;
use Setono\SyliusImagePlugin\Client\Cloudflare\Response\ImageResponse;
use Setono\SyliusImagePlugin\Client\Cloudflare\Response\VariantCollectionResponse;
use Setono\SyliusImagePlugin\Client\Cloudflare\Response\VariantDetailsResponse;
use Setono\SyliusImagePlugin\Client\Cloudflare\Response\VariantResponse;
use Symfony\Component\DependencyInjection\Container;
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

    public function uploadImage(string $filename, array $metadata = []): ImageResponse
    {
        $formData = new FormDataPart([
            'file' => DataPart::fromPath($filename),
        ]);

        return new ImageResponse(
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

    public function getImageDetails(string $identifier): ImageResponse
    {
        return new ImageResponse(
            $this->httpClient->request(
                'GET',
                sprintf('/client/v4/accounts/%s/images/v1/%s', $this->accountIdentifier, $identifier)
            )->toArray()
        );
    }

    public function getVariant(string $identifier): VariantDetailsResponse
    {
        return new VariantDetailsResponse(
            $this->httpClient->request(
                'GET',
                sprintf('/client/v4/accounts/%s/images/v1/variants/%s', $this->accountIdentifier, $identifier)
            )->toArray()
        );
    }

    public function getVariants(): VariantCollectionResponse
    {
        return new VariantCollectionResponse(
            $this->httpClient->request(
                'GET',
                sprintf('/client/v4/accounts/%s/images/v1/variants', $this->accountIdentifier)
            )->toArray()
        );
    }

    public function createVariant(string $name, $options, bool $neverRequireSignedUrls = false): VariantResponse
    {
        $name = ucfirst(Container::camelize($name));

        if (!$options instanceof VariantOptions) {
            $options = new VariantOptions($options);
        }

        return new VariantResponse(
            $this->httpClient->request(
                'POST',
                sprintf('/client/v4/accounts/%s/images/v1/variants', $this->accountIdentifier),
                [
                    'json' => [
                        'id' => $name,
                        'options' => $options->toArray(),
                        'neverRequireSignedURLs' => $neverRequireSignedUrls,
                    ],
                ]
            )->toArray()
        );
    }
}
