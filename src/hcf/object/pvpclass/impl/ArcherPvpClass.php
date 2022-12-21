<?php

declare(strict_types=1);

namespace hcf\object\pvpclass\impl;

use hcf\object\profile\Profile;
use hcf\object\pvpclass\EnergyPvpClass;
use hcf\object\pvpclass\PvpClass;
use hcf\utils\ServerUtils;
use pocketmine\item\Item;
use function array_merge;

final class ArcherPvpClass extends PvpClass implements EnergyPvpClass {

    /**
     * @param Profile $profile
     * @param Item    $itemHand
     */
    public function onItemInteract(Profile $profile, Item $itemHand): void {
        if (($classItem = $this->getValidItem($itemHand)) === null) return;
        if (($instance = $profile->getInstance()) === null || !$instance->isConnected()) return;

        if (!$classItem->isApplyOnSelf()) return;

        if ($classItem->getEnergy() > $profile->getEnergy()) {
            $instance->sendMessage(ServerUtils::replacePlaceholders('NOT_ENOUGH_ENERGY', [
                'energy' => (string) $classItem->getEnergy(),
                'current_energy' => (string) $profile->getEnergy()
            ]));

            return;
        }

        $profile->decreaseEnergy($classItem->getEnergy());
        $instance->getInventory()->setItemInHand($itemHand->setCount($itemHand->getCount() - 1));

        foreach ($classItem->getEffects() as $effectInstance) {
            $instance->getEffects()->add($effectInstance);
        }
    }

    /**
     * @param Profile $profile
     * @param Item    $itemHand
     */
    public function onHeldItem(Profile $profile, Item $itemHand): void {
        $this->onItemInteract($profile, $itemHand);
    }

    /**
     * @return string
     */
    public function getScoreboardPlaceholder(): string {
        return 'archer_class_lines';
    }

    /**
     * @param Profile $profile
     *
     * @return array<string, string>
     */
    public function getScoreboardLines(Profile $profile): array {
        return array_merge(parent::getScoreboardLines($profile), [
            'archer_energy' => (string) $profile->getEnergy()
        ]);
    }

    /**
     * @return int
     */
    public function getMaxEnergy(): int {
        return 60;
    }
}