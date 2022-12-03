<?php

declare(strict_types=1);

namespace hcf\command\faction\arguments;

use abstractplugin\command\Argument;
use hcf\command\faction\ProfileArgumentTrait;
use hcf\factory\FactionFactory;
use hcf\HCFLanguage;
use hcf\object\faction\FactionData;
use hcf\object\profile\Profile;
use hcf\object\profile\ProfileData;
use hcf\utils\HCFUtils;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function count;

final class JoinArgument extends Argument {
    use ProfileArgumentTrait;

    /**
     * @param Player  $sender
     * @param Profile $profile
     * @param string  $label
     * @param array   $args
     */
    public function onPlayerExecute(Player $sender, Profile $profile, string $label, array $args): void {
        if (count($args) === 0) {
            $sender->sendMessage(TextFormat::RED . 'Usage: /' . $label . ' join <faction|player>');

            return;
        }

        if ($profile->getFactionId() !== null) {
            $sender->sendMessage(HCFLanguage::COMMAND_FACTION_ATTEMPT_JOIN()->build());

            return;
        }

        if ((($faction = FactionFactory::getInstance()->getFactionName($args[0]))) === null) {
            $sender->sendMessage(HCFLanguage::FACTION_NOT_INVITED()->build($args[0]));

            return;
        }

        if (!$faction->isOpen() && !$faction->hasPendingInvite($sender->getXuid())) {
            $sender->sendMessage(HCFLanguage::FACTION_NOT_INVITED()->build($faction->getName()));

            return;
        }

        if ($faction->getRegenStatus() === FactionData::STATUS_PAUSED) {
            $sender->sendMessage(HCFUtils::replacePlaceholders('PLAYER_ATTEMPT_JOIN_ON_FREEZE'));

            return;
        }

        $faction->removePendingInvite($sender->getXuid());

        FactionFactory::getInstance()->joinFaction($profile, $faction, ProfileData::MEMBER_ROLE);
    }
}