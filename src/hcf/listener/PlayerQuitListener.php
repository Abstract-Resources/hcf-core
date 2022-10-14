<?php

declare(strict_types=1);

namespace hcf\listener;

use hcf\factory\ProfileFactory;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;

final class PlayerQuitListener implements Listener {

    /**
     * @param PlayerQuitEvent $ev
     *
     * handle it on monitor to allow spawn loggerbait and more
     * @priority MONITOR
     */
    public function onPlayerQuitEvent(PlayerQuitEvent $ev): void {
        ProfileFactory::getInstance()->unregisterProfile($ev->getPlayer()->getXuid());
    }
}