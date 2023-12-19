<?php

declare(strict_types=1);

namespace Setono\SyliusImagePlugin\Synchronizer;

use Doctrine\Persistence\ManagerRegistry;
use Setono\DoctrineObjectManagerTrait\ORM\ORMManagerTrait;
use Setono\SyliusImagePlugin\Config\Preset;
use Setono\SyliusImagePlugin\Config\PresetRegistryInterface;
use Setono\SyliusImagePlugin\ImageGenerator\ImageGeneratorRegistryInterface;
use Setono\SyliusImagePlugin\Model\PresetConfigurationInterface;
use Setono\SyliusImagePlugin\Repository\PresetConfigurationRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Webmozart\Assert\Assert;

final class VariantConfigurationSynchronizer implements VariantConfigurationSynchronizerInterface
{
    use ORMManagerTrait;

    private FactoryInterface $variantConfigurationFactory;

    private PresetConfigurationRepositoryInterface $presetConfigurationRepository;

    private PresetRegistryInterface $presetRegistry;

    private ImageGeneratorRegistryInterface $generatorRegistry;

    public function __construct(
        FactoryInterface $variantConfigurationFactory,
        PresetConfigurationRepositoryInterface $presetConfigurationRepository,
        PresetRegistryInterface $presetRegistry,
        ManagerRegistry $managerRegistry,
        ImageGeneratorRegistryInterface $generatorRegistry
    ) {
        $this->variantConfigurationFactory = $variantConfigurationFactory;
        $this->presetConfigurationRepository = $presetConfigurationRepository;
        $this->presetRegistry = $presetRegistry;
        $this->managerRegistry = $managerRegistry;
        $this->generatorRegistry = $generatorRegistry;
    }

    public function synchronize(bool $runSetup = true): VariantConfigurationSynchronizationResultInterface
    {
        $syncResult = new VariantConfigurationSynchronizationResult();

        if ($runSetup) {
            foreach ($this->generatorRegistry->all() as $name => $generator) {
                $setupResult = $generator->setup($this->getPresetsByGenerator($name));
                $syncResult->addSetupResult($setupResult);
            }

            $this->generatorRegistry->getDefault()->setup($this->getPresetsByGenerator(null));
        }

        $presetConfiguration = $this->presetConfigurationRepository->findNewest();
        if (null !== $presetConfiguration) {
            $presets = $presetConfiguration->getPresets();

            if (self::presetsEquals($presets, $this->presetRegistry->all())) {
                $syncResult->addMessage('Variant configuration has not changed.');

                return $syncResult;
            }
        }

        /** @var PresetConfigurationInterface|object $presetConfiguration */
        $presetConfiguration = $this->variantConfigurationFactory->createNew();
        Assert::isInstanceOf($presetConfiguration, PresetConfigurationInterface::class);

        $presetConfiguration->setPresets($this->presetRegistry->all());

        $manager = $this->getManager($presetConfiguration);
        $manager->persist($presetConfiguration);
        $manager->flush();

        return $syncResult;
    }

    /**
     * todo add to preset registry if used more than once
     *
     * @return list<Preset>
     */
    private function getPresetsByGenerator(?string $generator): array
    {
        $presets = [];

        foreach ($this->presetRegistry->all() as $preset) {
            if ($preset->generator === $generator) {
                $presets[] = $preset;
            }
        }

        return $presets;
    }

    /**
     * Returns false if
     * - the two arrays does not have the same amount of presets
     * - the two arrays does not have the same presets
     *
     * @param array<string, Preset> $presets1
     * @param array<string, Preset> $presets2
     */
    private static function presetsEquals(array $presets1, array $presets2): bool
    {
        if (count($presets1) !== count($presets2)) {
            return false;
        }

        foreach ($presets1 as $preset1) {
            $equals = false;
            foreach ($presets2 as $preset2) {
                if ($preset1->equals($preset2)) {
                    $equals = true;

                    break;
                }
            }

            if (!$equals) {
                return false;
            }
        }

        return true;
    }
}
