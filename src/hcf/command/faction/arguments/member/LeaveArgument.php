<?php

declare(strict_types=1);

namespace hcf\command\faction\arguments\member;

use abstractplugin\command\Argument;
use hcf\command\faction\ProfileArgumentTrait;
use hcf\factory\FactionFactory;
use hcf\object\profile\Profile;
use hcf\object\profile\ProfileData;
use hcf\utils\ServerUtils;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

final class LeaveArgument extends Argument {
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

        if ($profile->getFactionRole() === ProfileData::LEADER_ROLE) {
            $sender->sendMessage(TextFormat::RED . 'Please use /' . $label . ' disband');

            return;
        }

        if (($claimRegion = FactionFactory::getInstance()->getFactionClaim($faction)) !== null && $claimRegion->getCuboid()->isInside($sender->getPosition())) {
            $sender->sendMessage(ServerUtils::replacePlaceholders('MUST_LEAVE_FACTION_TERRITORY'));

            return;
        }

        $sender->sendMessage(ServerUtils::replacePlaceholders('PLAYER_FACTION_LEFT'));
        $faction->broadcastMessage(ServerUtils::replacePlaceholders('FACTION_PLAYER_LEFT', [
        	'player' => $sender->getName()
        ]));

        $faction->flushMember($sender->getXuid(), $sender->getName());

        $profile->setFactionId(null);
        $profile->setFactionRole(ProfileData::MEMBER_ROLE);
        $profile->forceSave(true);
    }
}