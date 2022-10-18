<?php

declare(strict_types=1);

namespace hcf\object\faction\query;

use hcf\object\faction\FactionData;
use hcf\thread\query\Query;
use hcf\utils\MySQL;

final class SaveFactionQuery extends Query {

    /**
     * @param FactionData $factionData
     */
    public function __construct(private FactionData $factionData) {}

    /**
     * @param MySQL $provider
     *
     * This function is executed on other Thread to prevent lag spike on Main thread
     */
    public function run(MySQL $provider): void {
        // TODO: Implement run() method.
    }

    /**
     * This function is executed on the Main Thread because need use some function of pmmp
     */
    public function onComplete(): void {
        // TODO: Implement onComplete() method.
    }
}