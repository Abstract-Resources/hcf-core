<?php

declare(strict_types=1);

namespace hcf\object\faction\query;

use hcf\thread\CommonThread;
use hcf\thread\LocalThreaded;
use hcf\thread\types\SQLDataSourceThread;
use hcf\thread\types\ThreadType;

final class DisbandFactionQuery implements LocalThreaded {

    /**
     * @param string $id
     */
    public function __construct(private string $id) {}

    /**
     * @return int
     */
    public function threadId(): int {
        return CommonThread::SQL_DATA_SOURCE;
    }

    /**
     * @param ThreadType $threadType
     *
     * This function is executed on other Thread to prevent lag spike on Main thread
     */
    public function run(ThreadType $threadType): void {
        if (!$threadType instanceof SQLDataSourceThread || $threadType->id() !== $this->threadId()) return;

        $provider = $threadType->getResource();

        $provider->prepareStatement('DELETE FROM factions WHERE id = ?');
        $provider->set($this->id);

        $provider->executeStatement()->close();
    }

    /**
     * This function is executed on the Main Thread because need use some function of pmmp
     */
    public function onComplete(): void {}
}