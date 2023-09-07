<?php

declare(strict_types=1);

namespace hcf\command\faction\arguments;

use abstractplugin\command\Argument;
use hcf\utils\ServerUtils;
use pocketmine\command\CommandSender;

final class HelpArgument extends Argument {

    /**
     * @param CommandSender $sender
     * @param string        $commandLabel
     * @param array         $args
     */
    public function onConsoleExecute(CommandSender $sender, string $commandLabel, array $args): void {
        $sender->sendMessage(ServerUtils::replacePlaceholders('SERVER_FACTIONS_HELP'));
    }
}