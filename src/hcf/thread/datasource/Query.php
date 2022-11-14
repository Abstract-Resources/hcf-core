<?php

declare(strict_types=1);

namespace hcf\thread\datasource;

abstract class Query {

    /**
     * @param MySQL $provider
     *
     * This function is executed on other Thread to prevent lag spike on Main thread
     */
    abstract public function run(MySQL $provider): void;

    /**
     * This function is executed on the Main Thread because need use some function of pmmp
     */
    abstract public function onComplete(): void;
}