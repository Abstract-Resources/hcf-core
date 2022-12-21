<?php

declare(strict_types=1);

namespace hcf\factory;

use hcf\HCFCore;
use hcf\object\profile\Profile;
use hcf\object\pvpclass\impl\ArcherPvpClass;
use hcf\object\pvpclass\impl\BardPvpClass;
use hcf\object\pvpclass\PvpClass;
use pocketmine\item\Item;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use function is_array;
use function is_string;

final class PvpClassFactory {
    use SingletonTrait;

    /** @var array<string, PvpClass> */
    private array $pvpClasses = [];

    public function init(): void {
        $classesStored = [
            'Bard' => BardPvpClass::class,
            'Archer' => ArcherPvpClass::class
        ];

        $config = new Config(HCFCore::getInstance()->getDataFolder() . 'classes.yml');

        foreach ($config->getAll() as $pvpClassName => $data) {
            if (!is_string($pvpClassName) || !is_array($data)) continue;

            /** @phpstan-var class-string<PvpClass> $className */
            $className = $classesStored[$data['class_name']] ?? null;

            if ($className === null) continue;

            $this->registerPvpClass(new $className(
                $pvpClassName,
                $data['custom_name'],
                PvpClass::parseItems($data['armor_contents'] ?? []),
                PvpClass::parseEffects($data['effects'] ?? []),
                $data['extra'] ?? []
            ));
        }
    }

    /**
     * @param PvpClass ...$pvpClasses
     */
    private function registerPvpClass(PvpClass...$pvpClasses): void {
        foreach ($pvpClasses as $pvpClass) {
            $this->pvpClasses[$pvpClass->getName()] = $pvpClass;

            $pvpClass->init();
        }
    }

    /**
     * @param string $pvpClassName
     *
     * @return PvpClass|null
     */
    public function getPvpClass(string $pvpClassName): ?PvpClass {
        return $this->pvpClasses[$pvpClassName] ?? null;
    }

    /**
     * @param Profile $profile
     * @param Item[]   $contents
     */
    public function attemptEquip(Profile $profile, array $contents): void {
        if (($pvpClass = $profile->getPvpClass()) !== null && !$pvpClass->isApplicableFor($contents)) {
            $pvpClass->onUnequip($profile);
        }

        foreach ($this->pvpClasses as $iterator) {
            if (!$iterator->isApplicableFor($contents)) continue;

            $iterator->onEquip($profile);

            break;
        }
    }
}