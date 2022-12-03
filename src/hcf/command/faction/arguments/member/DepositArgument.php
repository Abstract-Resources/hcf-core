<?php

declare(strict_types=1);

namespace hcf\command\faction\arguments\member;

use abstractplugin\command\Argument;
use hcf\command\faction\ProfileArgumentTrait;
use hcf\object\profile\Profile;
use pocketmine\player\Player;

final class DepositArgument extends Argument {
    use ProfileArgumentTrait;

    /**
     * @param Player  $sender
     * @param Profile $profile
     * @param string  $label
     * @param array   $args
     */
    public function onPlayerExecute(Player $sender, Profile $profile, string $label, array $args): void {
        // TODO: Implement onPlayerExecute() method.
    }
}