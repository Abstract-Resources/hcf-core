<?php

declare(strict_types=1);

namespace hcf\command\faction\arguments\officer;

use abstractplugin\command\Argument;
use hcf\command\faction\ProfileArgumentTrait;
use hcf\factory\FactionFactory;
use hcf\HCFCore;
use hcf\object\profile\Profile;
use hcf\object\profile\ProfileData;
use hcf\utils\HCFUtils;
use pocketmine\player\Player;
use pocketmine\utils\Config;

final class SetHomeArgument extends Argument {
    use ProfileArgumentTrait;

    /**
     * @param Player  $sender
     * @param Profile $profile
     * @param string  $label
     * @param array   $args
     */
    public function onPlayerExecute(Player $sender, Profile $profile, string $label, array $args): void {
        if ($profile->getFactionId() === null || ($faction = FactionFactory::getInstance()->getFaction($profile->getFactionId())) === null) {
            $sender->sendMessage(HCFUtils::replacePlaceholders('COMMAND_FACTION_NOT_IN'));

            return;
        }

        if (!ProfileData::isAtLeast($profile->getFactionRole(), ProfileData::OFFICER_ROLE)) {
            $sender->sendMessage(HCFUtils::replacePlaceholders('COMMAND_FACTION_NOT_OFFICER'));

            return;
        }

        if (($factionAt = FactionFactory::getInstance()->getFactionAt($loc = $sender->getLocation())) !== null && $factionAt->getId() !== $faction->getId()) {
            $sender->sendMessage(HCFUtils::replacePlaceholders('YOU_CANNOT_DO_THIS_HERE'));

            return;
        }

        $config = new Config(HCFCore::getInstance()->getDataFolder() . 'hq.json');
        $config->set($faction->getId(), [
        	'x' => $loc->getFloorX(),
        	'y' => $loc->getFloorY(),
        	'z' => $loc->getFloorZ(),
        	'world' => $loc->getWorld()->getFolderName(),
        	'yaw' => $loc->yaw,
        	'pitch' => $loc->pitch
        ]);
        $config->save();

        $faction->broadcastMessage(HCFUtils::replacePlaceholders('FACTION_HOME_CHANGED', [
        	'player' => $sender->getName()
        ]));
    }
}