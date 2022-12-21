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
use function array_chunk;
use function count;
use function igbinary_unserialize;
use function is_array;
use function ksort;
use function min;

final class FactionListThreaded implements Query {

    /** @var array<int, array> */
    private array $results = [];
    /** @var bool */
    private bool $found = false;
    /** @var int */
    private int $maxPages;

    /**
     * @param string $factionsSerialized
     * @param string $targetName
     * @param int    $pageNumber
     */
    public function __construct(
        private string $factionsSerialized,
        private string $targetName,
        private int $pageNumber
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

            $factions[$faction->getName()] = $faction;
        }

        ksort($factions, SORT_NATURAL | SORT_FLAG_CASE);

        $factions = array_chunk($factions, 10);

        if ($this->pageNumber > ($this->maxPages = count($factions))) {
            $this->results[] = [TextFormat::RED . 'There ' . ($this->maxPages === 1 ? 'is only ' . $this->maxPages . ' page' : 'are only ' . $this->maxPages . ' pages') . '.', []];

            return;
        }

        $pageNumber = min(count($factions), $this->pageNumber);
        if($pageNumber < 1){
            $this->pageNumber = $pageNumber = 1;
        }

        if (count($factions = $factions[$pageNumber - 1] ?? []) <= 0) {
            $this->results[] = ['NON_FACTIONS_FOUND', []];

            return;
        }

        /** @var array<int, Faction> $factions */
        foreach ($factions as $index => $faction) {
            $this->results[] = ['FACTION_LIST_ARGUMENT', ['index' => (string) ($index + 1), 'name' => $faction->getName()]];
        }

        $this->found = true;
    }

    /**
     * This function is executed on the Main Thread because need use some function of pmmp
     */
    public function onComplete(): void {
        if (($target = Server::getInstance()->getPlayerExact($this->targetName)) === null) {
            if ($this->targetName !== 'CONSOLE') return;

            $target = new ConsoleCommandSender(Server::getInstance(), Server::getInstance()->getLanguage());
        }

        $message = '';
        if ($this->found) {
            $message .= TextFormat::colorize("&7&m--------------------------------------\n&7 Faction List &f(Page " . $this->pageNumber . '/' . $this->maxPages . ')') . "\n\n";
        }

        foreach ($this->results as $result) {
            [$key, $args] = $result;

            $message .= ServerUtils::replacePlaceholders($key, $args) . "\n";
        }

        if ($this->found) {
            $message .= TextFormat::GOLD . "\n You are currently on " . TextFormat::WHITE . 'Page ' . $this->pageNumber . '/' . $this->maxPages . TextFormat::GOLD . ".\n";
            $message .= TextFormat::GOLD . ' To view other pages, use ' . TextFormat::YELLOW . '/faction list <page#>' . TextFormat::GOLD . ".\n";
            $message .= TextFormat::colorize('&7&m--------------------------------------');
        }

        $target->sendMessage($message);
    }
}