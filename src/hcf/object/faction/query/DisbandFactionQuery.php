<?php

declare(strict_types=1);

namespace hcf\object\faction\query;

use hcf\thread\datasource\MySQL;
use hcf\thread\datasource\Query;

final class DisbandFactionQuery extends Query {

    /**
     * @param string $id
     */
    public function __construct(private string $id) {}

    /**
     * @param MySQL $provider
     *
     * This function is executed on other Thread to prevent lag spike on Main thread
     */
    public function run(MySQL $provider): void {
        $provider->prepareStatement('DELETE FROM factions WHERE id = ?');
        $provider->set($this->id);

        $provider->executeStatement()->close();
    }

    /**
     * This function is executed on the Main Thread because need use some function of pmmp
     */
    public function onComplete(): void {}
}