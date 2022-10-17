<?php

declare(strict_types=1);

namespace hcf\thread\query;

use hcf\utils\MySQL;

abstract class Query {

    /**
     * @param MySQL $provider
     *
     * This function is executed on other Thread to prevent lag spike on Main thread
     */
    public abstract function run(MySQL $provider): void;

    /**
     * This function is executed on the Main Thread because need use some function of pmmp
     */
    public abstract function onComplete(): void;
}