<?php

declare(strict_types=1);

namespace hcf\command\faction;

use hcf\factory\ProfileFactory;
use hcf\object\profile\Profile;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

trait ProfileArgumentTrait {

    /**
     * @param CommandSender $sender
     * @param string        $label
     * @param array         $args
     */
    public function onConsoleExecute(CommandSender $sender, string $label, array $args): void {
        if (!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . 'Run this command in-game');

            return;
        }

        if (($profile = ProfileFactory::getInstance()->getIfLoaded($sender->getXuid())) === null) {
            $sender->sendMessage(TextFormat::RED . 'Run this command in-game');

            return;
        }

        $this->onPlayerExecute($sender, $profile, $label, $args);
    }

    /**
     * @param Player  $sender
     * @param Profile $profile
     * @param string  $label
     * @param array   $args
     */
    abstract public function onPlayerExecute(Player $sender, Profile $profile, string $label, array $args): void;
}