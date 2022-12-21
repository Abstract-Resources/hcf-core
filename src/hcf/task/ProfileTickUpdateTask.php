<?php

declare(strict_types=1);

namespace hcf\task;

use hcf\factory\ProfileFactory;
use hcf\object\pvpclass\EnergyPvpClass;
use pocketmine\scheduler\Task;
use pocketmine\Server;

final class ProfileTickUpdateTask extends Task {

    /**
     * Actions to execute when run
     */
    public function onRun(): void {
        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            if (($profile = ProfileFactory::getInstance()->getIfLoaded($player->getXuid())) === null) continue;

            /** @var $pvpClass EnergyPvpClass */
            if (($pvpClass = $profile->getPvpClass()) instanceof EnergyPvpClass && $pvpClass->getMaxEnergy() > $profile->getEnergy()) {
                $profile->increaseEnergy(1);
            }

            $profile->updateScoreboard();
        }
    }
}