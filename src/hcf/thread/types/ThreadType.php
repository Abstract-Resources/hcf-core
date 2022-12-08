<?php

declare(strict_types=1);

namespace hcf\thread\types;

use ThreadedLogger;

interface ThreadType {

    /**
     * @param ThreadedLogger $logger
     */
    public function init(ThreadedLogger $logger): void;

    /**
     * @return int
     */
    public function id(): int;
}