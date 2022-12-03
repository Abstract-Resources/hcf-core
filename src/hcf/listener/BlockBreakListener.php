<?php

declare(strict_types=1);

namespace hcf\listener;

use hcf\factory\FactionFactory;
use hcf\object\ClaimRegion;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;

final class BlockBreakListener implements Listener {

    /**
     * @param BlockBreakEvent $ev
     *
     * @priority NORMAL
     */
    public function onBlockBreakEvent(BlockBreakEvent $ev): void {
        $player = $ev->getPlayer();

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