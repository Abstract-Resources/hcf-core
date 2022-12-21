<?php

declare(strict_types=1);

namespace hcf\command\faction\threaded;

use hcf\object\faction\Faction;
use hcf\thread\datasource\MySQL;
use hcf\thread\datasource\Query;
use hcf\utils\ServerUtils;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use function array_map;
use function arsort;
use function count;
use function igbinary_unserialize;
use function implode;
use function is_array;

final class FactionTopThreaded implements Query {

    /** @var array */
    private array $result = [];

    /**
     * @param string $factionsSerialized
     * @param string $targetName
     */
    public function __construct(
        private string $factionsSerialized,
        private string $targetName
    ) {}

    /**
     * @param MySQL $provider
     *
     * This function is executed on other Thread to prevent lag spike on Main thread
     */
    public function run(MySQL $provider): void {
        $factionsUnserialized = igbinary_unserialize($this->factionsSerialized);

        if (!is_array($factionsUnserialized)) return;

        $factions = [];

        foreach ($factionsUnserialized as $faction) {
            if (!$faction instanceof Faction) continue;

            $factions[$faction->getName()] = $faction->getPoints();
        }

        arsort($factions);

        if (count($factions) === 0) {
            $this->result[] = [TextFormat::RED . 'No factions', []];

            return;
        }

        $index = 1;

        foreach ($factions as $factionName => $factionPoints) {
            $this->result[] = ['FACTION_TOP_ARGUMENT', ['index' => $index++, 'faction' => $factionName, 'points' => $factionPoints]];

            if ($index >= 10) break;
        }
    }

    /**
     * This function is executed on the Main Thread because need use some function of pmmp
     */
    public function onComplete(): void {
        if (($target = Server::getInstance()->getPlayerExact($this->targetName)) === null) {
            if ($this->targetName !== 'CONSOLE') return;

            $target = new ConsoleCommandSender(Server::getInstance(), Server::getInstance()->getLanguage());
        }

        $target->sendMessage(ServerUtils::replacePlaceholders('FACTION_TOP_INFO', ['message' => implode("\n", array_map(function (array $result): string {
            return ServerUtils::replacePlaceholders($result[0], $result[1]);
        },$this->result))]));
    }
}