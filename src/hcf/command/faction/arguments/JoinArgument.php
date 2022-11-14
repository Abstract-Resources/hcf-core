<?php

declare(strict_types=1);

namespace hcf\command\faction\arguments;

use abstractplugin\command\Argument;
use abstractplugin\command\PlayerArgumentTrait;
use hcf\factory\FactionFactory;
use hcf\factory\ProfileFactory;
use hcf\object\faction\FactionData;
use hcf\object\profile\ProfileData;
use hcf\utils\HCFUtils;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function count;

final class JoinArgument extends Argument {
    use PlayerArgumentTrait;

    /**
     * @param Player $sender
     * @param string $label
     * @param array  $args
     */
    public function onPlayerExecute(Player $sender, string $label, array $args): void {
        if (count($args) === 0) {
            $sender->sendMessage(TextFormat::RED . 'Usage: /' . $label . ' accept <faction|player>');

            return;
        }

        if (($profile = ProfileFactory::getInstance()->getIfLoaded($sender->getXuid())) === null) return;

        if ($profile->getFactionId() !== null) {
            $sender->sendMessage(HCFUtils::replacePlaceholders('COMMAND_FACTION_ATTEMPT_JOIN'));

            return;
        }

        if ((($faction = FactionFactory::getInstance()->getFactionName($args[0]))) === null) {
            $sender->sendMessage(HCFUtils::replacePlaceholders('FACTION_NOT_INVITED', $args[0]));

            return;
        }

        if (!$faction->isOpen() && !$faction->hasPendingInvite($sender->getXuid())) {
            $sender->sendMessage(HCFUtils::replacePlaceholders('FACTION_NOT_INVITED', $faction->getName()));

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