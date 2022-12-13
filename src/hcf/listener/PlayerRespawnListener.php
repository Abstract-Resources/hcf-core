<?php

declare(strict_types=1);

namespace hcf\listener;

use hcf\factory\ProfileFactory;
use hcf\object\profile\ProfileTimer;
use hcf\utils\HCFUtils;
use pocketmine\entity\Location;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerRespawnEvent;

final class PlayerRespawnListener implements Listener {

    /**
     * @param PlayerRespawnEvent $ev
     *
     * @priority NORMAL
     */
    public function onPlayerRespawnEvent(PlayerRespawnEvent $ev): void {
        $player = $ev->getPlayer();

        if (($profile = ProfileFactory::getInstance()->getIfLoaded($player->getXuid())) === null) return;

        $ev->setRespawnPosition($to = HCFUtils::getDefaultWorld()->getSpawnLocation());

        $profile->toggleProfileTimer(ProfileTimer::PVP_TAG, 60 * 60);

        (new PlayerMoveEvent($player, $player->getLocation(), Location::fromObject($to, $to->getWorld())))->call();
    }
}