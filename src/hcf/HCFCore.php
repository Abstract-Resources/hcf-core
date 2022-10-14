<?php

declare(strict_types=1);

namespace hcf;

use hcf\listener\PlayerLoginListener;
use hcf\listener\PlayerQuitListener;
use hcf\thread\ThreadPool;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;

final class HCFCore extends PluginBase {
    use SingletonTrait;

    public function onEnable(): void {
        ThreadPool::getInstance()->init(self::getConfigInt('thread-idle', 3));

        $this->getServer()->getPluginManager()->registerEvents(new PlayerLoginListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerQuitListener(), $this);
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

    public static function debug(string $debug): void {

    }
}