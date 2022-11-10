<?php

declare(strict_types=1);

namespace hcf\thread;

use hcf\HCFCore;
use hcf\thread\query\MySQLCredentials;
use hcf\thread\query\Query;
use pocketmine\network\mcpe\raklib\PthreadsChannelReader;
use pocketmine\Server;
use pocketmine\snooze\SleeperNotifier;
use pocketmine\utils\SingletonTrait;
use Threaded;
use function is_int;
use function is_string;
use function unserialize;

final class ThreadPool {
    use SingletonTrait;

    /** @var CoreThread[] */
    private array $threads = [];
    /** @var SleeperNotifier|null */
    private ?SleeperNotifier $notifier;

    /**
     * @param int $threadsIdle
     */
    public function init(int $threadsIdle): void {
        [$address, $port] = MySQLCredentials::parseHost(HCFCore::getConfigString('mysql.host'));

        $credentials = new MySQLCredentials(
            is_string($address) ? $address : '127.0.0.1',
            is_int($port) ? $port : 3306,
            HCFCore::getConfigString('mysql.username'),
            HCFCore::getConfigString('mysql.password'),
            HCFCore::getConfigString('mysql.dbname')
        );

        $threadToMainBuffer = new Threaded();
        $this->notifier = new SleeperNotifier();

        for ($i = 0; $i < $threadsIdle; $i++) {
            $thread = new CoreThread(
                $credentials,
                Server::getInstance()->getLogger(),
                $threadToMainBuffer,
                $this->notifier
            );
            $thread->start(PTHREADS_INHERIT_CONSTANTS);

            $this->threads[] = $thread;
        }

        $threadToMainReader = new PthreadsChannelReader($threadToMainBuffer);
        Server::getInstance()->getTickSleeper()->addNotifier($this->notifier, function () use ($threadToMainReader): void {
            /** @var $query Query */
            while (($payload = $threadToMainReader->read()) !== null) {
                $query = unserialize($payload, ['allowed_classes' => true]);

                if (!$query instanceof Query) continue;

                $query->onComplete();
            }
        });
    }

    /**
     * @param Query $query
     *
     * @return bool
     */
    public function submit(Query $query): bool {
        /** @var CoreThread|null $betterThread */
        $betterThread = null;

        $attempts = 0;

        foreach ($this->threads as $thread) {
            $attempts++;

            if ($betterThread === null) {
                $betterThread = $thread;

                continue;
            }

            if ($betterThread->lastUsage > $thread->lastUsage) continue;
            // TODO: Check it because maybe the thread was interrupted
            if ($thread->lastUpdate > $betterThread->lastUpdate) continue;

            $betterThread = $thread;
        }

        if ($betterThread === null) {
            HCFCore::debug('No available thread found.');

            return false;
        }

        HCFCore::debug('An available \'Thread\' was found after ' . $attempts . ' attempts');

        $betterThread->submit($query);

        return true;
    }

    public function close(): void {
        foreach ($this->threads as $thread) $thread->shutdown();

        if ($this->notifier !== null) {
            Server::getInstance()->getTickSleeper()->removeNotifier($this->notifier);
        }
    }
}