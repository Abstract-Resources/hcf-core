<?php

declare(strict_types=1);

namespace hcf\command\faction\arguments;

use hcf\command\PlayerArgument;
use hcf\factory\FactionFactory;
use hcf\HCFLanguage;
use hcf\object\profile\ProfileData;
use pocketmine\player\Player;

final class DisbandArgument extends PlayerArgument {

    /**
     * @param Player $sender
     * @param string $commandLabel
     * @param array  $args
     */
    public function handle(Player $sender, string $commandLabel, array $args): void {
        if (($profile = $this->getTarget($sender)) === null) return;

        if ($profile->getFactionId() === null || ($faction = FactionFactory::getInstance()->getFaction($profile->getFactionId())) === null) {
            $sender->sendMessage(HCFLanguage::COMMAND_FACTION_NOT_IN()->build());

            return;
        }

        if (!ProfileData::isAtLeast($profile->getFactionRole(), ProfileData::LEADER_ROLE)) {
            $sender->sendActionBarMessage(HCFLanguage::COMMAND_FACTION_NOT_LEADER()->build());

            return;
        }

        FactionFactory::getInstance()->disbandFaction($faction);
    }
}