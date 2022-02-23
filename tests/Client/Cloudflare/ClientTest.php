<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusImagePlugin\Client\Cloudflare;

use PHPUnit\Framework\TestCase;
use Setono\SyliusImagePlugin\Client\Cloudflare\Client;
use Setono\SyliusImagePlugin\Client\Cloudflare\DTO\VariantOptions;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * @covers \Setono\SyliusImagePlugin\Client\Cloudflare\Client
 */
final class ClientTest extends TestCase
{
    private bool $live = false;

    private string $accountIdentifier = 'account_identifier';

    private string $apiToken = 'api_key';

    protected function setUp(): void
    {
        $live = (bool) getenv('CLOUDFLARE_LIVE');
        if (false !== $live) {
            $this->live = true;

            $apiToken = getenv('CLOUDFLARE_API_TOKEN');
            if (false !== $apiToken) {
                $this->apiToken = $apiToken;
            }

            $accountIdentifier = getenv('CLOUDFLARE_ACCOUNT_IDENTIFIER');
            if (false !== $accountIdentifier) {
                $this->accountIdentifier = $accountIdentifier;
            }
        }
    }

    /**
     * @test
     */
    public function it_gets_variants(): void
    {
        $mockResponse = new MockResponse(
            <<<RESPONSE
{
  "result": {
    "variants": {
      "test": {
        "id": "test",
        "options": {
          "fit": "scale-down",
          "height": 100,
          "metadata": "none",
          "width": 100
        },
        "neverRequireSignedURLs": false
      }
    }
  },
  "result_info": null,
  "success": true,
  "errors": [],
  "messages": []
}
RESPONSE
        );
        $client = $this->getClient($mockResponse);
        $response = $client->getVariants();
        self::assertTrue($response->success);
    }

    /**
     * @test
     */
    public function it_creates_variant(): void
    {
        $variantName = 'Test';

        $mockResponse = new MockResponse(sprintf('{"result": {"id": "%s"},"result_info": null,"success": true,"errors": [],"messages": []}', $variantName));
        $client = $this->getClient($mockResponse);
        $response = $client->createVariant($variantName, [
            'fit' => VariantOptions::FIT_CROP,
            'metadata' => VariantOptions::METADATA_NONE,
            'width' => 100,
            'height' => 100,
        ]);

        self::assertSame($variantName, $response->result->id);

        if ($this->live) {
            $createdVariant = $client->getVariant($variantName)->result->variant;
            self::assertSame($variantName, $createdVariant->id);
            self::assertNotNull($createdVariant->options);
            self::assertEquals([
                'fit' => VariantOptions::FIT_CROP,
                'metadata' => VariantOptions::METADATA_NONE,
                'width' => 100,
                'height' => 100,
            ], $createdVariant->options->toArray());

            // todo remove variant again
        }
    }

    private function getClient(MockResponse $response): Client
    {
        if ($this->live) {
            $httpClient = HttpClient::createForBaseUri('https://api.cloudflare.com', [
                'headers' => [
                    'Authorization' => sprintf('Bearer %s', $this->apiToken),
                ],
            ]);
        } else {
            $httpClient = new MockHttpClient($response);
        }

        return new Client($httpClient, $this->accountIdentifier);
    }
}
