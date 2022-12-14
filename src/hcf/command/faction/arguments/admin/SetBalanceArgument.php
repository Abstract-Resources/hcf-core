<?php

declare(strict_types=1);

namespace hcf\command\faction\arguments\admin;

use abstractplugin\command\Argument;
use hcf\factory\FactionFactory;
use hcf\utils\HCFUtils;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use function count;
use function intval;
use function is_numeric;

final class SetBalanceArgument extends Argument {

    /**
     * @param CommandSender $sender
     * @param string        $commandLabel
     * @param array         $args
     */
    public function onConsoleExecute(CommandSender $sender, string $commandLabel, array $args): void {
        if (count($args) < 2) {
            $sender->sendMessage(TextFormat::RED . 'Usage: /' . $commandLabel . ' setbalance <faction> <new_balance>');

            return;
        }

        if (($faction = FactionFactory::getInstance()->getFactionName($args[0])) === null) {
            $sender->sendMessage(TextFormat::RED . 'Faction ' . $args[0] . ' not found');

            return;
        }

        if (!is_numeric($args[1])) {
            $sender->sendMessage(HCFUtils::replacePlaceholders('AMOUNT_MUST_BE_POSITIVE'));

            return;
        }

        $faction->setBalance(intval($args[1]));
        $faction->forceSave(true);

        $faction->broadcastMessage(TextFormat::GREEN . 'Your balance was changed to ' . $args[1] . ' by a administrator!');

        $sender->sendMessage(TextFormat::GREEN . 'Balance of ' . $faction->getName() . ' was changed to ' . $args[1]);
    }
}