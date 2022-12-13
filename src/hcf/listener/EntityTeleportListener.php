<?php

declare(strict_types=1);

namespace hcf\listener;

use hcf\factory\ProfileFactory;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\player\Player;

final class EntityTeleportListener implements Listener {

    /**
     * @param EntityTeleportEvent $ev
     *
     * @priority NORMAL
     */
    public function onEntityTeleportEvent(EntityTeleportEvent $ev): void {
        $player = $ev->getEntity();

        if (!$player instanceof Player) return;
        if (($profile = ProfileFactory::getInstance()->getIfLoaded($player->getXuid())) === null) return;

        $loc = $player->getLocation();

        $from = $ev->getFrom();
        $to = $ev->getTo();

        (new PlayerMoveEvent(
            $player,
            Location::fromObject($from, $from->getWorld(), $loc->yaw, $loc->pitch),
            Location::fromObject($to, $to->getWorld(), $loc->yaw, $loc->pitch)
        ))->call();
    }
}