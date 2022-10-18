<?php

declare(strict_types=1);

namespace hcf\command;

use hcf\factory\ProfileFactory;
use hcf\object\profile\Profile;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

abstract class PlayerArgument implements ParentCommand {

    /**
     * @param string      $name
     * @param string|null $permission
     * @param array       $aliases
     */
    public function __construct(
        private string $name,
        private ?string $permission = null,
        private array $aliases = []
    ) {}

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getPermission(): ?string {
        return $this->permission;
    }

    /**
     * @return array
     */
    public function getAliases(): array {
        return $this->aliases;
    }

    /**
     * @param CommandSender $sender
     * @param string        $commandLabel
     * @param array         $args
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if (!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . 'Run this command in-game');

            return;
        }

        $this->handle($sender, $commandLabel, $args);
    }

    /**
     * @param Player $sender
     * @param string $commandLabel
     * @param array  $args
     */
    public abstract function handle(Player $sender, string $commandLabel, array $args): void;

    /**
     * @param Player $player
     *
     * @return Profile|null
     */
    protected function getTarget(Player $player): ?Profile {
        if (($profile = ProfileFactory::getInstance()->getIfLoaded($player->getXuid())) === null) {
            $player->sendMessage(TextFormat::RED . 'Please re-join to load your profile.');

            return null;
        }

        return $profile;
    }
}