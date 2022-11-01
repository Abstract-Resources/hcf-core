<?php

declare(strict_types=1);

namespace hcf\object\faction\query;

use hcf\thread\query\Query;
use hcf\utils\MySQL;

final class LoadFactionsQuery extends Query {

    /**
     * @param MySQL $provider
     */
    public function run(MySQL $provider): void {
    }

    public function onComplete(): void {
        // TODO: Implement onComplete() method.
    }
}