<?php

declare(strict_types=1);

namespace hcf\command\faction\arguments;

use hcf\command\PlayerArgument;
use hcf\factory\FactionFactory;
use hcf\HCFCore;
use hcf\object\faction\Faction;
use hcf\object\profile\ProfileData;
use hcf\utils\HCFUtils;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use Ramsey\Uuid\Uuid;

final class CreateArgument extends PlayerArgument {

	/**
	 * @param Player $sender
	 * @param string $commandLabel
	 * @param array  $args
	 */
	public function handle(Player $sender, string $commandLabel, array $args): void {
		if (!isset($args[0])) {
			$sender->sendMessage(TextFormat::RED . 'Usage: /' . $commandLabel . ' create <name>');

			return;
		}

		if (($profile = $this->getTarget($sender)) === null) return;

		if ($profile->getFactionId() !== null) {
			$sender->sendMessage(HCFUtils::replacePlaceholders('YOU_ALREADY_IN_FACTION'));

			return;
		}

		if (FactionFactory::getInstance()->getFactionName($args[0]) !== null) {
			$sender->sendMessage(HCFUtils::replacePlaceholders('FACTION_ALREADY_EXISTS', $args[0]));

			return;
		}

        $faction = new Faction(
            Uuid::uuid4()->toString(),
            $args[0],
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