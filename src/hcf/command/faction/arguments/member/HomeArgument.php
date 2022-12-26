<?php

declare(strict_types=1);

namespace hcf\command\faction\arguments\member;

use abstractplugin\command\Argument;
use hcf\command\ProfileArgumentTrait;
use hcf\factory\FactionFactory;
use hcf\HCFCore;
use hcf\object\profile\Profile;
use hcf\object\profile\ProfileTimer;
use hcf\utils\ServerUtils;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;

final class HomeArgument extends Argument {
    use ProfileArgumentTrait;

    /**
     * @param Player  $sender
     * @param Profile $profile
     * @param string  $label
     * @param array   $args
     */
    public function onPlayerExecute(Player $sender, Profile $profile, string $label, array $args): void {
        if ($profile->getFactionId() === null || ($faction = FactionFactory::getInstance()->getFaction($profile->getFactionId())) === null) {
            $sender->sendMessage(ServerUtils::replacePlaceholders('COMMAND_FACTION_NOT_IN'));

            return;
        }

        if (($profileTimer = $profile->getProfileTimer(ProfileTimer::COMBAT_TAG)) !== null && $profileTimer->isRunning()) {
            $sender->sendMessage(ServerUtils::replacePlaceholders('COMMAND_FACTION_HOME_SPAWN_TIMER'));

            return;
        }

        if (($profileTimer = $profile->getProfileTimer(ProfileTimer::PVP_TAG)) !== null && $profileTimer->isRunning()) {
            $sender->sendMessage(ServerUtils::replacePlaceholders('COMMAND_FACTION_HOME_PVP_TIMER'));

            return;
        }

        if (($profileTimer = $profile->getProfileTimer(ProfileTimer::HOME_TAG)) !== null && $profileTimer->isRunning()) {
            $sender->sendMessage(ServerUtils::replacePlaceholders('COMMAND_FACTION_HOME_PVP_TIMER'));

            return;
        }

        if (($loc = $faction->getHqLocation()) === null) {
            $sender->sendMessage(ServerUtils::replacePlaceholders('COMMAND_FACTION_HOME_NOT_SET'));

            return;
        }

        $profile->toggleProfileTimer(ProfileTimer::HOME_TAG);

        if (($profileTimer = $profile->getProfileTimer(ProfileTimer::HOME_TAG)) === null || !$profileTimer->isRunning()) {
            return;
        }

        HCFCore::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($loc, $sender): void {
            if (!$sender->isConnected()) return;

            $sender->teleport($loc);

            $sender->sendMessage(TextFormat::GREEN . 'Successfully teleported!');
        }), $profileTimer->getRemainingTime() * 20);
    }
}