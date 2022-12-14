<?php

declare(strict_types=1);

namespace hcf\listener;

use hcf\factory\ProfileFactory;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemHeldEvent;

final class PlayerItemHeldListener implements Listener {

    /**
     * @param PlayerItemHeldEvent $ev
     *
     * @priority NORMAL
     * @handleCancelled
     */
    public function onPlayerItemHeldEvent(PlayerItemHeldEvent $ev): void {
        $player = $ev->getPlayer();

        if (($profile = ProfileFactory::getInstance()->getIfLoaded($player->getXuid())) === null) {
            $ev->cancel();

            return;
        }

        if (($pvpClass = $profile->getPvpClass()) === null) return;

        $pvpClass->onHeldItem($profile);
    }
}