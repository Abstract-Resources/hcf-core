<?php

declare(strict_types=1);

namespace hcf\command\faction\arguments\admin;

use abstractplugin\command\Argument;
use hcf\factory\FactionFactory;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

final class DisbandAllArgument extends Argument {

    /**
     * @param CommandSender $sender
     * @param string        $commandLabel
     * @param array         $args
     */
    public function onConsoleExecute(CommandSender $sender, string $commandLabel, array $args): void {
        foreach (FactionFactory::getInstance()->getFactionsStored() as $faction) {
            FactionFactory::getInstance()->disbandFaction($faction);
        }

        $sender->sendMessage(TextFormat::YELLOW . 'All factions was successfully disbanded!');
    }
}