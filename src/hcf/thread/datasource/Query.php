<?php

declare(strict_types=1);

namespace hcf\thread\datasource;

interface Query {

    /**
     * @param MySQL $provider
     *
     * This function is executed on other Thread to prevent lag spike on Main thread
     */
    public function run(MySQL $provider): void;

    /**
     * This function is executed on the Main Thread because need use some function of pmmp
     */
    public function onComplete(): void;
}