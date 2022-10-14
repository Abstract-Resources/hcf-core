<?php

declare(strict_types=1);

namespace hcf\thread\query;

use hcf\utils\MySQL;

abstract class Query {

    /**
     * @param MySQL $provider
     */
    public abstract function run(MySQL $provider): void;

    public abstract function onComplete(): void;
}