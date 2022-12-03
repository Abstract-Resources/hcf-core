<?php

declare(strict_types=1);

namespace hcf\command\faction\arguments\leader;

use abstractplugin\command\Argument;
use hcf\command\faction\ProfileArgumentTrait;
use hcf\factory\FactionFactory;
use hcf\HCFLanguage;
use hcf\object\ClaimRegion;
use hcf\object\profile\Profile;
use hcf\object\profile\ProfileData;
use hcf\utils\HCFUtils;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function in_array;

final class ClaimArgument extends Argument {
    use ProfileArgumentTrait;

    /**
     * @param Player  $sender
     * @param Profile $profile
     * @param string  $label
     * @param array   $args
     */
    public function onPlayerExecute(Player $sender, Profile $profile, string $label, array $args): void {
        if ($profile->getFactionId() === null || FactionFactory::getInstance()->getFaction($profile->getFactionId()) === null) {
            $sender->sendMessage(HCFLanguage::COMMAND_FACTION_NOT_IN()->build());

            return;
        }

        if (!ProfileData::isAtLeast($profile->getFactionRole(), ProfileData::LEADER_ROLE)) {
            $sender->sendActionBarMessage(HCFLanguage::COMMAND_FACTION_NOT_LEADER()->build());

            return;
        }

        if (in_array($profile->getClaimRegion()->getName(), [HCFUtils::REGION_SPAWN, HCFUtils::REGION_WARZONE], true)) {
            $sender->sendMessage(HCFLanguage::YOU_CANT_CLAIM_HERE()->build());

            return;
        }

        // TODO: Check if the faction not have an claim region

        $sender->sendMessage(HCFUtils::replacePlaceholders('PLAYER_FACTION_' . (($found = ClaimRegion::getIfClaiming($sender) !== null) ? 'STOPPED' : 'STARTED') . '_CLAIMING'));

        $item = self::getClaimingWand();
        if (!$found && !$sender->getInventory()->canAddItem($item)) {
            $sender->sendMessage(TextFormat::RED . 'Your inventory is full!');

            return;
        }

        if ($found) {
            ClaimRegion::flush($sender->getXuid());

            $sender->getInventory()->remove($item);

            return;
        }

        $sender->getInventory()->addItem($item);

        ClaimRegion::store($sender);
    }

    public static function getClaimingWand(): Item {
        $item = VanillaItems::DIAMOND_HOE();
        $item->setCustomName(TextFormat::colorize('&r&6Claim Tool'));
        $item->getNamedTag()->setByte('claim_type', 1);

        return $item;
    }
}