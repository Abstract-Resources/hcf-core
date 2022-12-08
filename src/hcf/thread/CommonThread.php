<?php

declare(strict_types=1);

namespace hcf\thread;

use Exception;
use hcf\thread\types\ThreadType;
use pocketmine\network\mcpe\raklib\SnoozeAwarePthreadsChannelWriter;
use pocketmine\snooze\SleeperNotifier;
use pocketmine\thread\Thread;
use Threaded;
use ThreadedLogger;
use function cli_set_process_title;
use function error_get_last;
use function gc_enable;
use function ini_set;
use function microtime;
use function register_shutdown_function;
use function serialize;

final class CommonThread extends Thread {

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

    /**
     * @param int             $threadId
     * @param array<int, ThreadType>           $threadTypes
     * @param ThreadedLogger  $logger
     * @param Threaded        $threadToMainBuffer
     * @param SleeperNotifier $notifier
     */
    public function __construct(
        private int $threadId,
        private array $threadTypes,
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
        try {
            @cli_set_process_title('Common Thread (' . $this->threadId . ')');

            gc_enable();
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
            ini_set('memory_limit', '512M');

            register_shutdown_function([$this, 'shutdownHandler']);

            foreach ($this->threadTypes as $threadType) $threadType->init($this->logger);

            while ($this->running) {
                foreach ($this->mainToThread as $index => $pending) {
                    if (!$pending instanceof LocalThreaded) {
                        unset($this->mainToThread[$index]);

                        continue;
                    }

                    $threadType = $this->threadTypes[$pending->threadId()] ?? null;

                    if ($threadType === null) {
                        unset($this->mainToThread[$index]);

                        continue;
                    }

                    $pending->run($threadType);

                    $this->threadToMainWriter->write(serialize($pending));

                    unset($this->mainToThread[$index]);
                }

                $this->lastUpdate = microtime(true);
            }
        } catch (Exception $e) {
            $this->logger->logException($e);
        }
    }

    /**
     * @param LocalThreaded $threaded
     */
    public function submit(LocalThreaded $threaded): void {
        $this->mainToThread[] = $threaded;

        $this->lastUsage = microtime(true);
    }

    public function shutdown(): void {
        $this->cleanShutdown = true;

        $this->running = false;

        $this->quit();
    }

    public function shutdownHandler(): void {
        if ($this->cleanShutdown) {
            $this->logger->info('Common Thread: Graceful shutdown complete');

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