<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\VariantGenerator;

use Gaufrette\File;
use Setono\SyliusImagePlugin\Client\Cloudflare\ClientInterface;
use Setono\SyliusImagePlugin\Client\Cloudflare\Response\ImageVariant;
use Setono\SyliusImagePlugin\Client\Cloudflare\Response\VariantResult;
use Setono\SyliusImagePlugin\Config\Variant;
use Setono\SyliusImagePlugin\Config\VariantCollectionInterface;
use Setono\SyliusImagePlugin\File\ImageVariantFile;
use Setono\SyliusImagePlugin\Model\ImageInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Contracts\HttpClient\ChunkInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Webmozart\Assert\Assert;

final class CloudflareVariantGenerator implements VariantGeneratorInterface
{
    public const NAME = 'cloudflare';

    private ClientInterface $client;

    private HttpClientInterface $httpClient;

    private MimeTypes $mimeTypes;

    private Filesystem $filesystem;

    public function __construct(
        ClientInterface $client,
        HttpClientInterface $httpClient,
        MimeTypes $mimeTypes,
        Filesystem $filesystem
    ) {
        $this->client = $client;
        $this->httpClient = $httpClient;
        $this->mimeTypes = $mimeTypes;
        $this->filesystem = $filesystem;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function generate(ImageInterface $image, File $file, VariantCollectionInterface $variantCollection): iterable
    {
        $tempDir = $this->getTempDir();

        $variants = $variantCollection->getByGenerator($this);

        try {
            $filename = sprintf('%s/%s', $tempDir, self::pathToFilename((string) $image->getPath()));
            $this->filesystem->dumpFile($filename, $file->getContent());

            $response = $this->client->uploadImage($filename);
            $cloudflareId = $response->result->id;

            $cloudflareVariants = self::resolveVariants($variants, $response->result->variants);

            $responses = [];
            foreach ($cloudflareVariants as $variant => $url) {
                $responses[] = $this->httpClient->request('GET', $url, [
                    'user_data' => [
                        'variant' => $variant,
                    ],
                ]);

                $responses[] = $this->httpClient->request('GET', $url, [
                    'user_data' => [
                        'variant' => $variant,
                    ],
                    'headers' => [
                        'Accept' => 'image/webp',
                    ],
                ]);

                $responses[] = $this->httpClient->request('GET', $url, [
                    'user_data' => [
                        'variant' => $variant,
                    ],
                    'headers' => [
                        'Accept' => 'image/avif',
                    ],
                ]);
            }

            /**
             * @var ResponseInterface $response
             * @var ChunkInterface $chunk
             */
            foreach ($this->httpClient->stream($responses) as $response => $chunk) {
                if (!$chunk->isLast()) {
                    continue;
                }

                $headers = $response->getHeaders();
                Assert::keyExists($headers, 'content-type');

                $contentTypes = $headers['content-type'];
                $contentType = array_shift($contentTypes);
                Assert::string($contentType);

                $userData = $response->getInfo('user_data');
                Assert::isArray($userData);
                Assert::keyExists($userData, 'variant');

                /** @var mixed $variant */
                $variant = $userData['variant'];
                Assert::string($variant);

                /**
                 * We specifically request webp and avif variants and want to save these with their appropriate extension.
                 * However, CloudFlare might convert png images to jpg or vice versa, but since we want to ensure that
                 * the 'original path' from the Image is always available as a processed variant, we don't use the
                 * Content-Type header from the response to determine extension, as this might differ from what we expect.
                 */
                switch ($contentType) {
                    case 'image/webp':
                    case 'image/avif':
                        $extension = $this->getExtensionFromMimeType($contentType);

                        break;
                    default:
                        $extension = pathinfo((string) $image->getPath(), \PATHINFO_EXTENSION);

                        break;
                }

                $filename = $this->filesystem->tempnam($tempDir, '');
                $this->filesystem->dumpFile($filename, $response->getContent());

                yield new ImageVariantFile($filename, $extension, $variant);
            }
        } finally {
            $this->filesystem->remove($tempDir);

            if (isset($cloudflareId)) {
                $this->client->deleteImage($cloudflareId);
            }
        }
    }

    /**
     * Takes a path like af/ed/saasdfsdafasdf.jpg and converts it to af-ed-saasdfsdafasdf.jpg
     */
    private static function pathToFilename(string $path): string
    {
        return str_replace('/', '-', $path);
    }

    /**
     * @param array<array-key, Variant> $variants
     * @param array<array-key, ImageVariant> $cloudflareVariants
     *
     * @return array<string, string>
     */
    private static function resolveVariants(array $variants, array $cloudflareVariants): array
    {
        $ret = [];

        foreach ($cloudflareVariants as $cloudflareVariant) {
            $variantName = Container::underscore($cloudflareVariant->name);

            foreach ($variants as $variant) {
                if ($variant->name === $variantName) {
                    $ret[$variantName] = $cloudflareVariant->url;
                }
            }
        }

        return $ret;
    }

    private function getExtensionFromMimeType(string $mimeType): string
    {
        $extensions = $this->mimeTypes->getExtensions($mimeType);

        return array_shift($extensions);
    }

    private function getTempDir(): string
    {
        $dir = sprintf('%s/%s', sys_get_temp_dir(), bin2hex(random_bytes(10)));
        $this->filesystem->mkdir($dir);

        return $dir;
    }

    public function setup(VariantCollectionInterface $variantCollection): SetupResultInterface
    {
        $setupResult = new SetupResult($this);

        // TODO: Add synchronization of changes to variant
        $this->ensureVariantsExists($variantCollection, $setupResult);

        return $setupResult;
    }

    private function ensureVariantsExists(VariantCollectionInterface $variantCollection, SetupResult $setupResult): void
    {
        $variants = $variantCollection->getByGenerator(self::NAME);

        if (empty($variants)) {
            return;
        }

        $existingVariants = array_map(static function (VariantResult $variantResult) {
            return Container::underscore($variantResult->id); // variants in Cloudflare are saved as camel case
        }, $this->client->getVariants()->result->variants);

        /** @var array<array-key, Variant> $variantsToCreate */
        $variantsToCreate = [];

        foreach ($variants as $variant) {
            if (in_array($variant->name, $existingVariants, true)) {
                continue;
            }

            $variantsToCreate[] = $variant;
        }

        foreach ($variantsToCreate as $item) {
            if (self::isVariantCreatable($item)) {
                $this->client->createVariant($item->name, [
                    'fit' => $item->fit,
                    'width' => $item->width,
                    'height' => $item->height,
                ]);
                $setupResult->addMessage(sprintf("Variant '%s' created at Cloudflare", $item->name));
            } else {
                $setupResult->addMessage(sprintf("UNABLE TO CREATE VARIANT '%s' ON CLOUDFLARE", $item->name));
            }
        }
    }

    private static function isVariantCreatable(Variant $variant): bool
    {
        return $variant->width !== null
            && $variant->height !== null
            && $variant->fit !== null
            && in_array($variant->fit, Variant::AVAILABLE_FITS, true);
    }
}
