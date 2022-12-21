<?php

declare(strict_types=1);

namespace hcf\object\pvpclass\impl;

use hcf\factory\FactionFactory;
use hcf\object\profile\Profile;
use hcf\object\pvpclass\PvpClass;
use hcf\utils\ServerUtils;
use pocketmine\item\Item;
use pocketmine\Server;
use function array_merge;

final class BardPvpClass extends PvpClass {

    /**
     * @param Profile $profile
     * @param Item    $itemHand
     */
    public function onItemInteract(Profile $profile, Item $itemHand): void {
        if (($factionId = $profile->getFactionId()) === null || ($faction = FactionFactory::getInstance()->getFaction($factionId)) === null) return;
        if (($classItem = $this->getValidItem($itemHand)) === null) return;
        if (($instance = $profile->getInstance()) === null || !$instance->isConnected()) return;

        if ($classItem->getEnergy() > $profile->getEnergy()) {
            $instance->sendMessage(ServerUtils::replacePlaceholders('NOT_ENOUGH_ENERGY', [
                'energy' => (string) $classItem->getEnergy(),
                'current_energy' => (string) $profile->getEnergy()
            ]));

            return;
        }

        $profile->decreaseEnergy($classItem->getEnergy());
        $instance->getInventory()->setItemInHand($itemHand->setCount($itemHand->getCount() - 1));

        foreach ($faction->getMembers() as $factionMember) {
            if (($target = Server::getInstance()->getPlayerExact($factionMember->getName())) === null || !$target->isConnected()) continue;
            if ($target->getPosition()->distance($instance->getPosition()) > 25) continue;

            foreach ($classItem->getEffects() as $effectInstance) $target->getEffects()->add($effectInstance);
        }

        if (!$classItem->isApplyOnSelf()) return;

        foreach ($classItem->getEffects() as $effectInstance) $instance->getEffects()->add($effectInstance);
    }

    public function onHeldItem(Profile $profile, Item $itemHand): void {
        $this->onItemInteract($profile, $itemHand);
    }

    /**
     * @return string
     */
    public function getScoreboardPlaceholder(): string {
        return 'bard_class_lines';
    }

    /**
     * @param Profile $profile
     *
     * @return array<string, string>
     */
    public function getScoreboardLines(Profile $profile): array {
        return array_merge(parent::getScoreboardLines($profile), [
        	'bard_energy' => (string) $profile->getEnergy()
        ]);
    }

    /**
     * @return int
     */
    public function getMaxEnergy(): int {
        return 120;
    }
}