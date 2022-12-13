<?php

declare(strict_types=1);

namespace hcf\factory;

use hcf\HCFCore;
use hcf\object\profile\Profile;
use hcf\object\pvpclass\impl\BardPvpClass;
use hcf\object\pvpclass\PvpClass;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\utils\Config;
use pocketmine\utils\Limits;
use pocketmine\utils\SingletonTrait;
use function is_array;
use function is_string;
use function mb_strtoupper;

final class PvpClassFactory {
    use SingletonTrait;

    /** @var array<string, PvpClass> */
    private array $pvpClasses = [];

    public function init(): void {
        $classesStored = [
        	'Bard' => BardPvpClass::class
        ];

        $config = new Config(HCFCore::getInstance()->getDataFolder() . 'classes.yml');

        foreach ($config->getAll() as $pvpClassName => $data) {
            if (!is_string($pvpClassName) || !is_array($data)) continue;

            /** @phpstan-var class-string<PvpClass> $className */
            $className = $classesStored[$pvpClassName] ?? null;

            if ($className === null) continue;

            $armorContents = [];
            foreach ($data['armor_contents'] as $slot => $armorData) {
                $item = VanillaItems::getAll()[mb_strtoupper($armorData['id'])] ?? null;

                if ($item === null) continue;

                foreach ($armorData['enchantments'] as $enchantmentData) {
                    if (($enchantment = VanillaEnchantments::getAll()[$enchantmentData[0]] ?? null) === null) continue;

                    $item->addEnchantment(new EnchantmentInstance($enchantment, $enchantmentData[1]));
                }

                $armorContents[$slot] = $item;
            }

            $effects = [];
            foreach ($data['effects'] as $effectData) {
                if (($effect = VanillaEffects::getAll()[$effectData['id']] ?? null) === null) continue;

                $effects[] = new EffectInstance($effect, $effectData['duration'] === -1 ? Limits::INT32_MAX : $effectData['duration'], $effectData['amplifier']);
            }

            $this->registerPvpClass(new $className(
                $pvpClassName,
                $data['custom_name'],
                $armorContents,
                $effects
            ));
        }
    }

    /**
     * @param PvpClass ...$pvpClasses
     */
    private function registerPvpClass(PvpClass...$pvpClasses): void {
        foreach ($pvpClasses as $pvpClass) {
            $this->pvpClasses[$pvpClass->getName()] = $pvpClass;
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