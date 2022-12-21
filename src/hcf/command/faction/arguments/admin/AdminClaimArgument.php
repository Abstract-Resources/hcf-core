<?php

declare(strict_types=1);

namespace hcf\command\faction\arguments\admin;

use abstractplugin\command\Argument;
use hcf\command\faction\arguments\leader\ClaimArgument;
use hcf\command\faction\ProfileArgumentTrait;
use hcf\object\ClaimRegion;
use hcf\object\profile\Profile;
use hcf\utils\ServerUtils;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function count;

final class AdminClaimArgument extends Argument {
    use ProfileArgumentTrait;

    /**
     * @param Player  $sender
     * @param Profile $profile
     * @param string  $label
     * @param array   $args
     */
    public function onPlayerExecute(Player $sender, Profile $profile, string $label, array $args): void {
        if (count($args) === 0) {
            $sender->sendMessage(TextFormat::RED . 'Please use /f adminclaim <claim_name>');

            return;
        }

        $sender->sendMessage(ServerUtils::replacePlaceholders('PLAYER_FACTION_' . (($found = ClaimRegion::getIfClaiming($sender) !== null) ? 'STOPPED' : 'STARTED') . '_CLAIMING'));

        $item = ClaimArgument::getClaimingWand(ClaimArgument::ADMIN_CLAIMING, $args[0]);
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
}