<?php

declare(strict_types=1);

namespace hcf\command\faction\threaded;

use hcf\object\faction\Faction;
use hcf\thread\CommonThread;
use hcf\thread\LocalThreaded;
use hcf\thread\types\ThreadType;
use hcf\utils\HCFUtils;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\Server;
use function array_chunk;
use function count;
use function igbinary_unserialize;
use function is_array;
use function ksort;
use function min;

final class FactionListThreaded implements LocalThreaded {

    private array $results = [];

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
     * @return int
     */
    public function threadId(): int {
        return CommonThread::COMMON_DATA_SOURCE;
    }

    /**
     * @param ThreadType $threadType
     *
     * This function is executed on other Thread to prevent lag spike on Main thread
     */
    public function run(ThreadType $threadType): void {
        $factionsUnserialized = igbinary_unserialize($this->factionsSerialized);

        if (!is_array($factionsUnserialized)) return;

        $factions = [];

        foreach ($factionsUnserialized as $faction) {
            if (!$faction instanceof Faction) continue;

            $factions[$faction->getName()] = $faction;
        }

        ksort($factions, SORT_NATURAL | SORT_FLAG_CASE);

        $factions = array_chunk($factions, 10);
        $pageNumber = min(count($factions), $this->pageNumber);
        if($pageNumber < 1){
            $pageNumber = 1;
        }

        if (count($factions = $factions[$pageNumber - 1] ?? []) <= 0) {
            $this->results[] = ['NON_FACTIONS_FOUND'];

            return;
        }

        /** @var array<int, Faction> $factions */
        foreach ($factions as $index => $faction) {
            $this->results[] = ['FACTION_LIST_ARGUMENT', ['index' => $index, 'name' => $faction->getName()]];
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

        foreach ($this->results as $result) {
            [$key, $args] = $result;

            $target->sendMessage(HCFUtils::replacePlaceholders($key, $args));
        }
    }
}