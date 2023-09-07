<?php

declare(strict_types=1);

namespace hcf\command\faction\arguments\leader;

use abstractplugin\command\Argument;
use hcf\command\ProfileArgumentTrait;
use hcf\factory\FactionFactory;
use hcf\object\profile\Profile;
use hcf\object\profile\ProfileData;
use hcf\utils\ServerUtils;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

final class UnclaimArgument extends Argument {
    use ProfileArgumentTrait;

    /**
     * @param Player  $sender
     * @param Profile $profile
     * @param string  $label
     * @param array   $args
     */
    public function onPlayerExecute(Player $sender, Profile $profile, string $label, array $args): void {
        if ($profile->getFactionId() === null || ($faction = FactionFactory::getInstance()->getFaction($profile->getFactionId())) === null) {
            $sender->sendMessage(ServerUtils::replacePlaceholders('COMMAND_FACTION_NOT_IN'));

            return;
        }

        if (!ProfileData::isAtLeast($profile->getFactionRole(), ProfileData::LEADER_ROLE)) {
            $sender->sendMessage(ServerUtils::replacePlaceholders('COMMAND_FACTION_NOT_OFFICER'));

            return;
        }

        if (($claimRegion = FactionFactory::getInstance()->getFactionClaim($faction)) === null) {
            $sender->sendMessage(TextFormat::RED . 'Faction claim not found');

            return;
        }

        FactionFactory::getInstance()->unregisterClaim($claimRegion->getCuboid(), $faction->getId());
    }
}