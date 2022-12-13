<?php

declare(strict_types=1);

namespace hcf\command\pvp\arguments;

use abstractplugin\command\Argument;
use hcf\command\faction\ProfileArgumentTrait;
use hcf\object\profile\Profile;
use hcf\object\profile\ProfileTimer;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

final class EnableArgument extends Argument {
    use ProfileArgumentTrait;

    /**
     * @param Player  $sender
     * @param Profile $profile
     * @param string  $label
     * @param array   $args
     */
    public function onPlayerExecute(Player $sender, Profile $profile, string $label, array $args): void {
        if (($profileTimer = $profile->getProfileTimer(ProfileTimer::PVP_TAG)) === null) {
            $sender->sendMessage(TextFormat::RED . 'An error occurred!');

            return;
        }

        if (!$profileTimer->isRunning()) {
            $sender->sendMessage(TextFormat::colorize(sprintf('&cYour %s &r&chas already been disabled.', $profileTimer->getNameColoured())));

            return;
        }

        $profileTimer->cancel();
        $profile->updateScoreboard();

        $sender->sendMessage(TextFormat::GREEN . 'Your pvp has been successfully enabled!');
    }
}