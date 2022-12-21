<?php

declare(strict_types=1);

namespace hcf\listener;

use hcf\factory\ProfileFactory;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemUseEvent;

final class PlayerItemUseListener implements Listener {

    /**
     * @param PlayerItemUseEvent $ev
     *
     * @priority NORMAL
     */
    public function onPlayerItemUseEvent(PlayerItemUseEvent $ev): void {
        $player = $ev->getPlayer();

        if (($profile = ProfileFactory::getInstance()->getIfLoaded($player->getXuid())) === null) {
            $ev->cancel();

            return;
        }

        if (($pvpClass = $profile->getPvpClass()) === null) return;

        $pvpClass->onHeldItem($profile, $ev->getItem());
    }
}