<?php

declare(strict_types=1);

namespace hcf\command\faction\arguments\admin;

use abstractplugin\command\Argument;
use hcf\utils\ServerUtils;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use function count;
use function intval;
use function is_numeric;

final class SotwArgument extends Argument {

    /**
     * @param CommandSender $sender
     * @param string        $commandLabel
     * @param array         $args
     */
    public function onConsoleExecute(CommandSender $sender, string $commandLabel, array $args): void {
        if (count($args) === 0) {
            $sender->sendMessage(TextFormat::RED . 'Please use /f sotw <time>');

            return;
        }

        if (!is_numeric($args[0])) {
            $sender->sendMessage(TextFormat::RED . 'Please provide a valid time');

            return;
        }

        ServerUtils::setSotwTime(intval($args[0]), true);

        $sender->sendMessage(TextFormat::GREEN . 'SOTW Time set to ' . ServerUtils::dateString(ServerUtils::getSotwTimeRemaining()));
    }
}