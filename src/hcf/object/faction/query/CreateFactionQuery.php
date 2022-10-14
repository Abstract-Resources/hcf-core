<?php

declare(strict_types=1);

namespace hcf\object\faction\query;

use hcf\thread\query\Query;
use hcf\utils\MySQL;
use pocketmine\thread\Thread;

final class CreateFactionQuery extends Query {

    /**
     * @param string $serialized
     */
    public function __construct(
        private string $serialized
    ) {}

    /**
     * @param MySQL $provider
     */
    public function run(MySQL $provider): void {
        //$unserialized = (array) unserialize($this->serialized);

        echo $this->serialized . ' executed on ' . Thread::getCurrentThreadId() . PHP_EOL;

        $this->serialized = 'adios' . PHP_EOL;
        // TODO: Do the sql query with the faction data unserialized
    }

    public function onComplete(): void {
        echo 'completed with value ' . $this->serialized . PHP_EOL;
    }
}