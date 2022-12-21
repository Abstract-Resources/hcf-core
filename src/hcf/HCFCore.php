<?php

declare(strict_types=1);

namespace hcf;

use hcf\command\faction\FactionCommand;
use hcf\command\koth\KothCommand;
use hcf\command\pvp\PvPCommand;
use hcf\factory\FactionFactory;
use hcf\factory\KothFactory;
use hcf\factory\ProfileFactory;
use hcf\factory\PvpClassFactory;
use hcf\listener\BlockBreakListener;
use hcf\listener\BlockPlaceListener;
use hcf\listener\claim\ClaimPlayerChatListener;
use hcf\listener\claim\ClaimPlayerInteractListener;
use hcf\listener\EntityDamageListener;
use hcf\listener\EntityTeleportListener;
use hcf\listener\PlayerDeathListener;
use hcf\listener\PlayerInteractListener;
use hcf\listener\PlayerItemHeldListener;
use hcf\listener\PlayerJoinListener;
use hcf\listener\PlayerLoginListener;
use hcf\listener\PlayerMoveListener;
use hcf\listener\PlayerQuitListener;
use hcf\listener\PlayerRespawnListener;
use hcf\object\faction\query\LoadFactionsQuery;
use hcf\task\ProfileTickUpdateTask;
use hcf\thread\ThreadPool;
use hcf\utils\ServerUtils;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use function is_float;
use function is_int;
use function is_string;

final class HCFCore extends PluginBase {
    use SingletonTrait;

    public function onEnable(): void {
        self::setInstance($this);

        $this->saveDefaultConfig();
        $this->saveResource('messages.yml');
        $this->saveResource('classes.yml');

        ServerUtils::load();

        PvpClassFactory::getInstance()->init();
        FactionFactory::getInstance()->init();
        KothFactory::getInstance()->init();

        ThreadPool::getInstance()->init(self::getConfigInt('thread-idle', 3));

        // TODO: Initialize all factions
        if (!ThreadPool::getInstance()->submit(new LoadFactionsQuery())) {
            $this->getLogger()->warning('An error occurred while trying load all factions stored.');
        }

        $this->getServer()->getCommandMap()->register(FactionCommand::class, new FactionCommand());
        $this->getServer()->getCommandMap()->register(KothCommand::class, new KothCommand());
        $this->getServer()->getCommandMap()->register(PvPCommand::class, new PvPCommand());

        $this->getServer()->getPluginManager()->registerEvents(new PlayerLoginListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerJoinListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new BlockBreakListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new BlockPlaceListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new EntityDamageListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerDeathListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerRespawnListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerMoveListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new EntityTeleportListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerInteractListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerItemHeldListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerQuitListener(), $this);

        $this->getServer()->getPluginManager()->registerEvents(new ClaimPlayerInteractListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new ClaimPlayerChatListener(), $this);

        $this->getScheduler()->scheduleRepeatingTask(new ProfileTickUpdateTask(), 20);
    }

    public function onDisable(): void {
        ProfileFactory::getInstance()->close();

        ThreadPool::getInstance()->close();

        ServerUtils::setSotwTime(ServerUtils::getSotwTimeRemaining(), true);
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