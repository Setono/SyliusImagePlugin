<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\VariantGenerator;

use Gaufrette\File;
use Setono\SyliusImagePlugin\Client\Cloudflare\ClientInterface;
use Setono\SyliusImagePlugin\File\ImageVariantFile;

final class CloudflareVariantGenerator implements VariantGeneratorInterface
{
    public const NAME = 'cloudflare';

    private ClientInterface $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function generate(File $file, array $variants, array $formats = ['jpg', 'webp', 'avif']): iterable
    {
        $filename = sprintf('%s/%s', sys_get_temp_dir(), uniqid('image-', true));

        file_put_contents($filename, $file->getContent());

        $response = $this->client->uploadImage($filename);

        foreach ($variants as $variant) {
            $imageVariantFilename = sprintf('%s/%s-%s', sys_get_temp_dir(), $variant->name, uniqid('', true));
            file_put_contents($imageVariantFilename, $file->getContent());
            yield new ImageVariantFile($imageVariantFilename, $variant->name, 'jpg');
        }
    }
}
