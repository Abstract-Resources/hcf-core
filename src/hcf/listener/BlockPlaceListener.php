<?php

declare(strict_types=1);

namespace hcf\listener;

use hcf\factory\FactionFactory;
use hcf\object\ClaimRegion;
use hcf\utils\ServerUtils;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\Server;

final class BlockPlaceListener implements Listener {

    /**
     * @param BlockPlaceEvent $ev
     *
     * @priority NORMAL
     */
    public function onBlockPlaceEvent(BlockPlaceEvent $ev): void {
        $player = $ev->getPlayer();

        if ($player->hasPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE)) return;

        $regionAt = FactionFactory::getInstance()->getRegionAt($ev->getBlock()->getPosition());
        if (($faction = FactionFactory::getInstance()->getFactionName($regionAt->getName())) === null && $regionAt->hasFlag(ClaimRegion::DISALLOW_BLOCK_PLACE)) {
            $ev->cancel();

            return;
        }

        if ($faction === null) return;

        if ($faction->getMember($player->getXuid()) !== null || $faction->getDeathsUntilRaidable(true) <= 0.0 || ServerUtils::isPurgeRunning()) {
            return;
        }

        $ev->cancel();
    }
}