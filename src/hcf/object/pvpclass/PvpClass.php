<?php

declare(strict_types=1);

namespace hcf\object\pvpclass;

use hcf\object\profile\Profile;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;

abstract class PvpClass {

    /**
     * @param string         $name
     * @param string         $customName
     * @param Item[]          $armorContents
     * @param EffectInstance[] $effects
     */
    public function __construct(
        private string $name,
        private string $customName,
        private array $armorContents,
        private array $effects
    ) {}

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
     * @return Item[]
     */
    public function getArmorContents(): array {
        return $this->armorContents;
    }

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
     */
    abstract public function onItemInteract(Profile $profile): void;

    /**
     * @param Profile $profile
     */
    abstract public function onHeldItem(Profile $profile): void;
}