<?php

declare(strict_types=1);

namespace hcf\command\faction\arguments;

use abstractplugin\command\Argument;
use hcf\factory\FactionFactory;
use hcf\HCFLanguage;
use hcf\object\faction\Faction;
use hcf\object\faction\FactionData;
use hcf\object\profile\ProfileData;
use hcf\utils\HCFUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function count;
use function implode;
use function sprintf;
use function strval;

final class WhoArgument extends Argument {

    /**
     * @param CommandSender $sender
     * @param string        $commandLabel
     * @param array         $args
     */
    public function onConsoleExecute(CommandSender $sender, string $commandLabel, array $args): void {
        /** @var Faction|null $faction */
        $faction = null;
        if (count($args) > 0) {
            $faction = FactionFactory::getInstance()->getFactionName($args[0]);
        } elseif ($sender instanceof Player) {
            $faction = FactionFactory::getInstance()->getPlayerFaction($sender);
        }

        if ($faction === null) {
            $sender->sendMessage(count($args) > 0 ? HCFLanguage::FACTION_NOT_FOUND()->build($args[0]) : TextFormat::RED . 'You need use /' . $commandLabel . ' who <faction_name>');

            return;
        }

        /** @var array<int, array<int, string>> $m */
        $m = [];
        foreach ($faction->getMembers() as $factionMember) {
            $m[$factionMember->getRole()][] = ($factionMember->isOnline() ? TextFormat::GREEN : TextFormat::GRAY) . $factionMember->getName() . sprintf('&e[&a%s&e]', strval($factionMember->getKills()));
        }

        $sender->sendMessage(HCFUtils::replacePlaceholders('FACTION_WHO_PLAYER', [
        	'faction' => $faction->getName(),
        	'players_count' => (string) count($faction->getMembers()),
        	'hq' => ($loc = $faction->getHqLocation()) === null ? 'None' : $loc->getFloorX() . ', ' . $loc->getFloorZ(),
        	'leaders' => implode(TextFormat::GRAY . ',', $m[ProfileData::LEADER_ROLE]),
        	'officers' => isset($m[ProfileData::OFFICER_ROLE]) ? implode(TextFormat::GRAY . ',', $m[ProfileData::OFFICER_ROLE] ?? []) : 'None',
        	'members' => isset($m[ProfileData::MEMBER_ROLE]) ? implode(TextFormat::GRAY . ',', $m[ProfileData::MEMBER_ROLE] ?? []) : 'None',
        	'balance' => (string) $faction->getBalance(),
        	'deaths_until_raidable' => (string) $faction->getDeathsUntilRaidable(true),
        	'points' => (string) $faction->getPoints(),
        	'time_until_regen' => $faction->getRegenStatus() === FactionData::STATUS_REGENERATING ? '&4Regenerating' : (($remainingRegenerating = $faction->getRemainingRegenerationTime()) <= 0 ? 'None' : HCFUtils::dateString($remainingRegenerating))
        ]));
    }
}