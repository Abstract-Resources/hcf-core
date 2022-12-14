<?php

declare(strict_types=1);

namespace hcf\listener;

use hcf\factory\FactionFactory;
use hcf\factory\ProfileFactory;
use hcf\object\ClaimRegion;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;

final class PlayerInteractListener implements Listener {

    /**
     * @param PlayerInteractEvent $ev
     *
     * @priority NORMAL
     */
    public function onPlayerInteractEvent(PlayerInteractEvent $ev): void {
        $player = $ev->getPlayer();

        if (($profile = ProfileFactory::getInstance()->getIfLoaded($player->getXuid())) === null) {
            $ev->cancel();

            return;
        }

        if (($pvpClass = $profile->getPvpClass()) !== null) $pvpClass->onItemInteract($profile);

        $regionAt = FactionFactory::getInstance()->getRegionAt($ev->getBlock()->getPosition());
        if (($faction = FactionFactory::getInstance()->getFactionName($regionAt->getName())) === null && $regionAt->isFlagEnabled(ClaimRegion::BLOCK_BREAK_FLAG)) {
            $ev->cancel();

            return;
        }

        if ($faction === null) return;

        if ($faction->getMember($player->getXuid()) !== null || $faction->getDeathsUntilRaidable(true) <= 0.0) {
            return;
        }

        $ev->cancel();
    }
}