<?php

declare(strict_types=1);

namespace hcf\command\faction\arguments\member;

use abstractplugin\command\Argument;
use hcf\command\faction\ProfileArgumentTrait;
use hcf\factory\FactionFactory;
use hcf\object\profile\Profile;
use hcf\utils\HCFUtils;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function count;
use function intval;
use function is_numeric;

final class DepositArgument extends Argument {
    use ProfileArgumentTrait;

    /**
     * @param Player  $sender
     * @param Profile $profile
     * @param string  $label
     * @param array   $args
     */
    public function onPlayerExecute(Player $sender, Profile $profile, string $label, array $args): void {
        if (count($args) === 0) {
            $sender->sendMessage(TextFormat::RED . 'Usage: /' . $label . ' deposit <amount|all>');

            return;
        }

        if ($profile->getFactionId() === null || ($faction = FactionFactory::getInstance()->getFaction($profile->getFactionId())) === null) {
            $sender->sendMessage(HCFUtils::replacePlaceholders('COMMAND_FACTION_NOT_IN'));

            return;
        }

        $amount = $args[0] === 'all' ? $profile->getBalance() : (is_numeric($args[0]) ? intval($args[0]) : 0);

        if ($amount <= 0) {
            $sender->sendMessage(HCFUtils::replacePlaceholders('AMOUNT_MUST_BE_POSITIVE'));

            return;
        }

        if ($amount > $profile->getBalance()) {
            $sender->sendMessage(HCFUtils::replacePlaceholders('NOT_ENOUGH_BALANCE', [
            	'amount' => (string) $amount,
            	'current_amount' => (string) $profile->getBalance()
            ]));

            return;
        }

        $profile->setBalance($profile->getBalance() - $amount);
        $faction->setBalance($faction->getBalance() + $amount);

        $profile->forceSave(true);
        $faction->forceSave(true);

        $faction->broadcastMessage(HCFUtils::replacePlaceholders('FACTION_MEMBER_DEPOSITED', [
        	'player' => $sender->getName(),
        	'amount' => (string) $amount
        ]));
    }
}