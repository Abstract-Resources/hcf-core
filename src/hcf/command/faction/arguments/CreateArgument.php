<?php

declare(strict_types=1);

namespace hcf\command\faction\arguments;

use abstractplugin\command\Argument;
use hcf\command\faction\ProfileArgumentTrait;
use hcf\factory\FactionFactory;
use hcf\HCFCore;
use hcf\HCFLanguage;
use hcf\object\faction\Faction;
use hcf\object\profile\Profile;
use hcf\object\profile\ProfileData;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use Ramsey\Uuid\Uuid;
use function time;

final class CreateArgument extends Argument {
    use ProfileArgumentTrait;

    /**
     * @param Player  $sender
     * @param Profile $profile
     * @param string  $label
     * @param array   $args
     */
    public function onPlayerExecute(Player $sender, Profile $profile, string $label, array $args): void {
		if (!isset($args[0])) {
			$sender->sendMessage(TextFormat::RED . 'Usage: /' . $label . ' create <name>');

			return;
		}

		if ($profile->getFactionId() !== null) {
			$sender->sendMessage(HCFLanguage::YOU_ALREADY_IN_FACTION()->build());

			return;
		}

		if (FactionFactory::getInstance()->getFactionName($args[0]) !== null) {
            $sender->sendMessage(HCFLanguage::FACTION_ALREADY_EXISTS()->build($args[0]));

			return;
		}

        $faction = new Faction(
            Uuid::uuid4()->toString(),
            $args[0],
            $sender->getXuid(),
            HCFCore::getConfigInt('factions.default-dtr'),
            0,
            time(),
            HCFCore::getConfigInt('factions.default-balance'),
            HCFCore::getConfigInt('factions.default-points')
        );
        $faction->forceSave(false);

        FactionFactory::getInstance()->registerFaction($faction);
        FactionFactory::getInstance()->joinFaction($profile, $faction, ProfileData::LEADER_ROLE);
    }
}