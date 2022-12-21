<?php

declare(strict_types=1);

namespace hcf\task;

use hcf\factory\ProfileFactory;
use hcf\object\ClaimCuboid;
use hcf\object\profile\ProfileTimer;
use hcf\object\pvpclass\EnergyPvpClass;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use function abs;

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

            $to = $player->getPosition();
            foreach ($profile->glassCached as $index => $position) {
                if (($profileTimer = $profile->getProfileTimer(ProfileTimer::COMBAT_TAG)) !== null && $profileTimer->isRunning()) {
                    if ($position->getWorld() !== $player->getWorld() || abs($to->getFloorX() - $position->getFloorX()) <= 5 && abs($position->getFloorY() - $to->getFloorY()) <= 6 && abs($to->getFloorZ() - $position->getFloorZ()) <= 5) {
                        continue;
                    }
                }

                unset($profile->glassCached[$index]);

                ClaimCuboid::sendBlockChange($player, $position->getWorld()->getBlock($position), $position);
            }

            $profile->updateScoreboard();
        }
    }
}