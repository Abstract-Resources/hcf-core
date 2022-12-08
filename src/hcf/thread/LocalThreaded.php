<?php

declare(strict_types=1);

namespace hcf\thread;

use hcf\thread\types\ThreadType;

interface LocalThreaded {

    /**
     * @return int
     */
    public function threadId(): int;

    /**
     * @param ThreadType $threadType
     *
     * This function is executed on other Thread to prevent lag spike on Main thread
     */
    public function run(ThreadType $threadType): void;

    /**
     * This function is executed on the Main Thread because need use some function of pmmp
     */
    public function onComplete(): void;
}