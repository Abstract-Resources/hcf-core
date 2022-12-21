<?php

declare(strict_types=1);

namespace hcf\object\pvpclass;

use hcf\object\profile\Profile;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\utils\Limits;

abstract class PvpClass {

    /**
     * @param string         $name
     * @param string         $customName
     * @param Item[]           $armorContents
     * @param EffectInstance[] $effects
     * @param array          $extra
     */
    public function __construct(
        private string $name,
        private string $customName,
        private array $armorContents,
        private array $effects,
        protected array $extra
    ) {}

    abstract public function init(): void;

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getCustomName(): string {
        return $this->customName;
    }

    /**
     * @return string
     */
    public function getScoreboardPlaceholder(): string {
        return '';
    }

    /**
     * @param Profile $profile
     *
     * @return array
     */
    public function getScoreboardLines(Profile $profile): array {
        return [
        	'class_name' => $this->getCustomName()
        ];
    }

    /**
     * @param Item[] $armorContents
     *
     * @return bool
     */
    public function isApplicableFor(array $armorContents): bool {
        foreach ($armorContents as $slot => $item) {
            $targetItem = $this->armorContents[$slot] ?? VanillaItems::AIR();

            if ($targetItem->getId() !== $item->getId()) return false;

            foreach ($targetItem->getEnchantments() as $enchantment) {
                if ($item->hasEnchantment($enchantment->getType())) continue;

                return false;
            }
        }

        return true;
    }

    /**
     * @param Profile $profile
     */
    public function onEquip(Profile $profile): void {
        if (($instance = $profile->getInstance()) === null || !$instance->isConnected()) return;

        $profile->setPvpClassName($this->getName());
        $profile->updateScoreboard();

        foreach ($this->effects as $effect) {
            $instance->getEffects()->add($effect);
        }
    }

    /**
     * @param Profile $profile
     */
    public function onUnequip(Profile $profile): void {
        if (($instance = $profile->getInstance()) === null || !$instance->isConnected()) return;

        $profile->setPvpClassName(null);
        $profile->updateScoreboard();

        foreach ($this->effects as $effect) {
            $instance->getEffects()->remove($effect->getType());
        }
    }

    /**
     * @param Profile $profile
     * @param Item    $itemHand
     */
    abstract public function onItemInteract(Profile $profile, Item $itemHand): void;

    /**
     * @param Profile $profile
     * @param Item $itemHand
     */
    abstract public function onHeldItem(Profile $profile, Item $itemHand): void;

    /**
     * @param array $itemsData
     *
     * @return Item[]
     */
    public static function parseItems(array $itemsData): array {
        /** @var Item[] $items */
        $items = [];

        foreach ($itemsData as $slot => $itemData) {
            $item = VanillaItems::getAll()[mb_strtoupper($itemData['id'])] ?? null;

            if ($item === null) continue;

            foreach ($itemData['enchantments'] ?? [] as $enchantmentData) {
                if (($enchantment = VanillaEnchantments::getAll()[$enchantmentData[0]] ?? null) === null) continue;

                $item->addEnchantment(new EnchantmentInstance($enchantment, $enchantmentData[1]));
            }

            $items[$slot] = $item;
        }

        return $items;
    }

    /**
     * @param array $effectsData
     *
     * @return EffectInstance[]
     */
    public static function parseEffects(array $effectsData): array {
        /** @var EffectInstance[] $effects */
        $effects = [];

        foreach ($effectsData as $effectData) {
            if (($effect = VanillaEffects::getAll()[$effectData['id']] ?? null) === null) continue;

            $effects[] = new EffectInstance($effect, $effectData['duration'] === -1 ? Limits::INT32_MAX : $effectData['duration'], $effectData['amplifier']);
        }

        return $effects;
    }
}