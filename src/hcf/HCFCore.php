<?php

declare(strict_types=1);

namespace hcf;

use hcf\command\faction\FactionCommand;
use hcf\factory\FactionFactory;
use hcf\listener\claim\ClaimPlayerChatListener;
use hcf\listener\claim\ClaimPlayerInteractListener;
use hcf\listener\PlayerLoginListener;
use hcf\listener\PlayerQuitListener;
use hcf\object\faction\query\LoadFactionsQuery;
use hcf\task\ProfileRegionUpdateTask;
use hcf\thread\ThreadPool;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use function is_file;
use function is_float;
use function is_int;
use function is_string;

final class HCFCore extends PluginBase {
    use SingletonTrait;

    public function onEnable(): void {
        self::setInstance($this);

        // Initialize the composer autoload
        if (!is_file($bootstrap = 'phar://' . Server::getInstance()->getPluginPath() . $this->getName() . '.phar/vendor/autoload.php')) {
            $this->getLogger()->error('Composer autoloader not found at ' . $bootstrap);
            $this->getLogger()->warning('Please install/update Composer dependencies or use provided build.');

            exit(1);
        }

        require_once($bootstrap);

        FactionFactory::getInstance()->init();
        ThreadPool::getInstance()->init(self::getConfigInt('thread-idle', 3));

        // TODO: Initialize all factions
        if (!ThreadPool::getInstance()->submit(new LoadFactionsQuery())) {
            $this->getLogger()->warning('An error occurred while trying load all factions stored.');
        }

        $this->getServer()->getCommandMap()->register(FactionCommand::class, new FactionCommand());

        $this->getServer()->getPluginManager()->registerEvents(new PlayerLoginListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerQuitListener(), $this);

        $this->getServer()->getPluginManager()->registerEvents(new ClaimPlayerInteractListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new ClaimPlayerChatListener(), $this);

        $this->getScheduler()->scheduleRepeatingTask(new ProfileRegionUpdateTask(), 30);
    }

    public function onDisable(): void {
        ThreadPool::getInstance()->close();
    }

    /**
     * @param string $key
     * @param int    $defaultValue
     *
     * @return int
     */
    public static function getConfigInt(string $key, int $defaultValue = 0): int {
        return is_int($value = self::getInstance()->getConfig()->getNested($key)) ? $value : $defaultValue;
    }

    /**
     * @param string $key
     * @param float  $defaultValue
     *
     * @return float
     */
    public static function getConfigFloat(string $key, float $defaultValue = 0.0): float {
        return is_float($value = self::getInstance()->getConfig()->getNested($key)) ? $value : $defaultValue;
    }

    /**
     * @param string $key
     * @param string $defaultValue
     *
     * @return string
     */
    public static function getConfigString(string $key, string $defaultValue = ''): string {
        return is_string($value = self::getInstance()->getConfig()->getNested($key)) ? $value : $defaultValue;
    }

    public static function debug(string $debug): void {

    }

    /**
     * @return bool
     */
    public static function released(): bool {
        return false;
    }
}