<?php

declare(strict_types=1);

namespace hcf\thread;

use hcf\thread\query\Query;
use pocketmine\network\mcpe\raklib\SnoozeAwarePthreadsChannelWriter;
use pocketmine\snooze\SleeperNotifier;
use pocketmine\thread\Thread;
use Threaded;
use ThreadedLogger;

final class CoreThread extends Thread {

    /** @var bool */
    private bool $running = true;
    /** @var bool */
    private bool $cleanShutdown = false;
    /** @var Threaded */
    private Threaded $mainToThread;
    /** @var SnoozeAwarePthreadsChannelWriter */
    private SnoozeAwarePthreadsChannelWriter $threadToMainWriter;

    /** @var float */
    public float $lastUsage = 0.0;
    /** @var float */
    public float $lastUpdate = 0.0;

    public function __construct(
        private ThreadedLogger $logger,
        Threaded $threadToMainBuffer,
        SleeperNotifier $notifier
    ) {
        $this->threadToMainWriter = new SnoozeAwarePthreadsChannelWriter($threadToMainBuffer, $notifier);
        $this->mainToThread = new Threaded();
    }

    /**
     * Runs code on the thread.
     */
    protected function onRun(): void {
        while ($this->running) {
            $this->lastUpdate = microtime(true);

            $pending = $this->mainToThread->shift();

            if (!$pending instanceof Query) continue;

            echo 'running ' . PHP_EOL;
            $pending->run();

            $this->threadToMainWriter->write(serialize($pending));
        }
    }

    /**
     * @param Query $query
     */
    public function submit(Query $query): void {
        $this->mainToThread[] = $query;

        $this->lastUsage = microtime(true);
    }

    public function shutdown(): void {
        $this->cleanShutdown = true;

        $this->running = false;
    }

    public function shutdownHandler(): void {
        if ($this->cleanShutdown) {
            $this->logger->info('Server Thread: Graceful shutdown complete');

            return;
        }

        $error = error_get_last();

        if ($error === null) {
            $this->logger->emergency('Server shutdown unexpectedly');
        } else {
            $this->logger->emergency('Fatal error: ' . $error['message'] . ' in ' . $error['file'] . ' on line ' . $error['line']);
        }
    }
}