<?php

declare(strict_types=1);

namespace hcf\listener;

use hcf\factory\FactionFactory;
use hcf\object\ClaimRegion;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;

final class BlockPlaceListener implements Listener {

    /**
     * @param BlockPlaceEvent $ev
     *
     * @priority NORMAL
     */
    public function onBlockPlaceEvent(BlockPlaceEvent $ev): void {
        $player = $ev->getPlayer();

        $regionAt = FactionFactory::getInstance()->getRegionAt($ev->getBlock()->getPosition());
        if (($faction = FactionFactory::getInstance()->getFactionName($regionAt->getName())) === null && $regionAt->isFlagEnabled(ClaimRegion::BLOCK_PLACE_FLAG)) {
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