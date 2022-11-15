<?php

declare(strict_types=1);

namespace hcf\command\faction\arguments\officer;

use abstractplugin\command\Argument;
use abstractplugin\command\PlayerArgumentTrait;
use hcf\factory\FactionFactory;
use hcf\factory\ProfileFactory;
use hcf\HCFCore;
use hcf\HCFLanguage;
use hcf\object\profile\ProfileData;
use hcf\utils\HCFUtils;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function count;

final class InviteArgument extends Argument {
    use PlayerArgumentTrait;

    /**
     * @param Player $sender
     * @param string $label
     * @param array  $args
     */
    public function onPlayerExecute(Player $sender, string $label, array $args): void {
        if (count($args) === 0) {
            $sender->sendMessage(TextFormat::RED . 'Usage: /' . $label . ' invite <player>');

            return;
        }

        if (($profile = ProfileFactory::getInstance()->getIfLoaded($sender->getXuid())) === null) return;

        if ($profile->getFactionId() === null || ($faction = FactionFactory::getInstance()->getFaction($profile->getFactionId())) === null) {
            $sender->sendMessage(HCFUtils::replacePlaceholders('COMMAND_FACTION_NOT_IN'));

            return;
        }

        if (!ProfileData::isAtLeast($profile->getFactionRole(), ProfileData::OFFICER_ROLE)) {
            $sender->sendMessage(HCFUtils::replacePlaceholders('COMMAND_FACTION_NOT_OFFICER'));

            return;
        }

        if ($faction->getDeathsUntilRaidable(true) <= 0.0) {
            $sender->sendMessage(HCFUtils::replacePlaceholders('COMMAND_FACTION_RAIDABLE'));

            return;
        }

        if (($targetProfile = ProfileFactory::getInstance()->getPlayerProfile($args[0])) === null) {
            $sender->sendMessage(HCFLanguage::PLAYER_NOT_FOUND()->build($args[0]));

            return;
        }

        if (HCFCore::released() && $targetProfile->getXuid() === $sender->getXuid()) {
            $sender->sendMessage(HCFLanguage::YOU_CANT_USE_THIS_ON_YOURSELF()->build());

            return;
        }

        if (HCFCore::released() && $targetProfile->getFactionId() !== null) {
            $sender->sendMessage(HCFLanguage::PLAYER_IN_FACTION()->build($targetProfile->getName()));

            return;
        }

        if ($faction->hasPendingInvite($targetProfile->getXuid())) {
            $sender->sendMessage(HCFLanguage::PLAYER_ALREADY_INVITED()->build($targetProfile->getName()));

            return;
        }

        // TODO: Change this to check what is the max members size
        if (count($faction->getMembers()) > 5) {
            $sender->sendMessage(HCFUtils::replacePlaceholders('FACTION_FULL', $faction->getName()));

            return;
        }

        if (($instance = $targetProfile->getInstance()) === null) return;

        $faction->addPendingInvite($targetProfile->getXuid());
        $faction->broadcastMessage(HCFLanguage::FACTION_INVITATION_SENT()->build($targetProfile->getName(), $sender->getName()));

        $instance->sendMessage(HCFLanguage::FACTION_INVITE_RECEIVED()->build($sender->getName(), $faction->getName()));
    }
}