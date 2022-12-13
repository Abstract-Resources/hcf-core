<?php

declare(strict_types=1);

namespace hcf\command\koth\arguments;

use abstractplugin\command\Argument;
use hcf\factory\FactionFactory;
use hcf\factory\KothFactory;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use function count;

final class StartArgument extends Argument {

    /**
     * @param CommandSender $sender
     * @param string        $commandLabel
     * @param array         $args
     */
    public function onConsoleExecute(CommandSender $sender, string $commandLabel, array $args): void {
        if (count($args) === 0) {
            $sender->sendMessage(TextFormat::RED . 'Please use /koth start <koth_name>');

            return;
        }

        if (($claimRegion = FactionFactory::getInstance()->getAdminClaim($args[0])) === null || FactionFactory::getInstance()->getKothClaim($args[0]) === null) {
            $sender->sendMessage(TextFormat::RED . 'Koth ' . $args[0] . ' not found!');

            return;
        }

        KothFactory::getInstance()->setCurrentKoth($claimRegion->getName());

        $sender->sendMessage(TextFormat::GREEN . 'Koth ' . $claimRegion->getName() . ' was successfully started!');
    }
}