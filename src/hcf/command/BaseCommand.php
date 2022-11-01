<?php

declare(strict_types=1);

namespace hcf\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use function array_map;
use function array_shift;
use function in_array;
use function strtolower;

abstract class BaseCommand extends Command implements ParentCommand {

    /** @var array<string, ParentCommand> */
    private array $parents = [];

    /**
     * @param ParentCommand ...$parents
     */
    protected function registerParent(ParentCommand...$parents): void {
        foreach ($parents as $parent) {
            $this->parents[strtolower($parent->getName())] = $parent;
        }
    }

    /**
     * @param string $argumentName
     *
     * @return ParentCommand|null
     */
    private function getParent(string $argumentName): ?ParentCommand {
        if (($issetParent = $this->parents[strtolower($argumentName)] ?? null) !== null) {
            return $issetParent;
        }

        foreach ($this->parents as $parent) {
            if (!in_array(strtolower($argumentName), array_map(fn(string $aliase) => strtolower($aliase), $parent->getAliases()), true)) {
                continue;
            }

            return $parent;
        }

        return null;
    }

    /**
     * @param CommandSender $sender
     * @param string        $commandLabel
     * @param array         $args
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if (($argumentName = array_shift($args)) === null) {
            $sender->sendMessage(TextFormat::RED . 'Usage: \'/' . $commandLabel . ' help\'');

            return;
        }

        if (($parent = $this->getParent($argumentName)) === null) {
            $sender->sendMessage(TextFormat::RED . 'Usage: \'/' . $commandLabel . ' help\'');

            return;
        }

        if (($permission = $parent->getPermission()) !== null && !$sender->hasPermission($permission)) {
            $sender->sendMessage(TextFormat::RED . 'You don\'t have permissions to use this command.');

            return;
        }

        $parent->execute($sender, $commandLabel, $args);
    }
}