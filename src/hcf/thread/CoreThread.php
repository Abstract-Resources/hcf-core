<?php

declare(strict_types=1);

namespace hcf\thread;

use hcf\thread\query\Query;
use hcf\utils\MySQL;
use hcf\utils\MySQLCredentials;
use pocketmine\network\mcpe\raklib\SnoozeAwarePthreadsChannelWriter;
use pocketmine\snooze\SleeperNotifier;
use pocketmine\thread\Thread;
use Threaded;
use ThreadedLogger;
use function error_get_last;
use function intval;
use function microtime;
use function min;
use function serialize;
use function sleep;

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

    /**
     * @param MySQLCredentials $credentials
     * @param ThreadedLogger   $logger
     * @param Threaded         $threadToMainBuffer
     * @param SleeperNotifier  $notifier
     */
    public function __construct(
        private MySQLCredentials $credentials,
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
        $resource = new MySQL(
            $this->credentials->getAddress(),
            $this->credentials->getUsername(),
            $this->credentials->getPassword(),
            $this->credentials->getDbname(),
            $this->credentials->getPort()
        );

        if ($resource->connect_error !== null) {
            throw new SqlException('An error occurred while connecting to \'' . $this->credentials->getAddress() . '@' . $this->credentials->getUsername() . '\'');
        }

        while ($this->running) {
            if (!$resource->ping()) {
                $success = false;

                $attempts = 0;

                while (!$success) {
                    $seconds = min(2 ** $attempts, PHP_INT_MAX);

                    $this->logger->warning('MySQL Connection failed! Trying reconnecting in ' . $seconds . ' seconds.');

                    sleep(intval($seconds));

                    $resource->connect(
                        $this->credentials->getAddress(),
                        $this->credentials->getUsername(),
                        $this->credentials->getPassword(),
                        $this->credentials->getDbname(),
                        $this->credentials->getPort()
                    );

                    if ($resource->connect_error !== null) {
                        $attempts++;

                        $this->logger->error('An error occurred while trying reconnect to \'' . $this->credentials->getAddress() . '@' . $this->credentials->getUsername() . '\': ' . $resource->connect_error);

                        continue;
                    }

                    $success = true;
                }

                $this->logger->info('Successfully database connection restored!');
            }

            $this->lastUpdate = microtime(true);

            $pending = $this->mainToThread->shift();

            if (!$pending instanceof Query) continue;

            echo 'running ' . PHP_EOL;
            $pending->run($resource);

            $this->threadToMainWriter->write(serialize($pending));
        }

        $resource->close();
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

        $this->quit();
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