<?php

declare(strict_types=1);

namespace hcf\task;

use hcf\factory\FactionFactory;
use hcf\factory\ProfileFactory;
use pocketmine\scheduler\Task;
use pocketmine\Server;

final class ProfileRegionUpdateTask extends Task {

    /**
     * Actions to execute when run
     */
    public function onRun(): void {
        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            if (($profile = ProfileFactory::getInstance()->getIfLoaded($player->getXuid())) === null) continue;

            $profile->setClaimRegion(FactionFactory::getInstance()->getRegionAt($player->getPosition()));
        }
    }
}