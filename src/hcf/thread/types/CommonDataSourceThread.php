<?php

declare(strict_types=1);

namespace hcf\thread\types;

use ThreadedLogger;

final class CommonDataSourceThread implements ThreadType {

    /**
     * @param ThreadedLogger $logger
     */
    public function init(ThreadedLogger $logger): void {
        // TODO: Implement init() method.
    }

    /**
     * @return int
     */
    public function id(): int {
        return 1;
    }
}