<?php

declare(strict_types=1);

namespace hcf\command\faction\arguments\leader;

use abstractplugin\command\Argument;
use abstractplugin\command\PlayerArgumentTrait;
use hcf\factory\FactionFactory;
use hcf\factory\ProfileFactory;
use hcf\HCFLanguage;
use hcf\object\profile\ProfileData;
use pocketmine\player\Player;

final class DisbandArgument extends Argument {
    use PlayerArgumentTrait;

    /**
     * @param Player $sender
     * @param string $label
     * @param array  $args
     */
    public function onPlayerExecute(Player $sender, string $label, array $args): void {
        if (($profile = ProfileFactory::getInstance()->getIfLoaded($sender->getXuid())) === null) return;

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