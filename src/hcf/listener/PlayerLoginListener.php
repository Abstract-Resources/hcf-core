<?php

declare(strict_types=1);

namespace hcf\listener;

use hcf\object\profile\query\LoadProfileQuery;
use hcf\thread\ThreadPool;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;

final class PlayerLoginListener implements Listener {

    /**
     * @param PlayerLoginEvent $ev
     *
     * @priority NORMAL
     */
    public function onPlayerLoginEvent(PlayerLoginEvent $ev): void {
        $player = $ev->getPlayer();

        if (ThreadPool::getInstance()->submit(new LoadProfileQuery($player->getXuid(), $player->getName()))) {
            return;
        }

        $ev->setKickMessage('Failed trying load your profile data');
        $ev->cancel();
    }
}