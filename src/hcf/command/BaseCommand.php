<?php

declare(strict_types=1);

namespace hcf\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

abstract class BaseCommand extends Command implements ParentCommand {

    /** @var array<string, ParentCommand> */
    private array $parents = [];

    /**
     * @param ParentCommand ...$parents
     */
    protected function registerParent(ParentCommand... $parents): void {
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
    }
}