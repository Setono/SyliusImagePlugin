<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\EventSubscriber;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Setono\SyliusImagePlugin\Config\Preset;
use Setono\SyliusImagePlugin\Model\ImageInterface;
use Setono\SyliusImagePlugin\Workflow\ProcessWorkflow;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;
use Webmozart\Assert\Assert;

final class PurgeLiipImagineCacheSubscriber implements EventSubscriberInterface
{
    private CacheManager $cacheManager;

    public static function getSubscribedEvents(): array
    {
        $event = sprintf(
            'workflow.%s.completed.%s',
            ProcessWorkflow::NAME,
            ProcessWorkflow::TRANSITION_FINISH
        );

        return [
            $event => 'purgeCache',
        ];
    }

    public function __construct(CacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    public function purgeCache(Event $event): void
    {
        /** @var ImageInterface|object $image */
        $image = $event->getSubject();
        Assert::isInstanceOf($image, ImageInterface::class);

        $path = $image->getPath();
        if ($path === null) {
            return;
        }

        $presetConfiguration = $image->getPresetConfiguration();
        if (null === $presetConfiguration) {
            return;
        }

        $presets = array_map(static function (Preset $preset) { return $preset->name; }, $presetConfiguration->getPresets());

        $this->cacheManager->remove($path, $presets);
    }
}
