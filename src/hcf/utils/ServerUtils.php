<?php

declare(strict_types=1);

namespace hcf\utils;

use Exception;
use hcf\HCFCore;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use pocketmine\world\World;
use function date;
use function gmdate;
use function implode;
use function is_array;
use function str_replace;
use function strval;
use function time;

final class ServerUtils {

    public const REGION_SPAWN = 'Spawn';
    public const REGION_WARZONE = 'Warzone';
    public const REGION_WILDERNESS = 'Wilderness';

    /** @var array */
    private static array $placeHolders = [];
    /** @var Config */
    private static Config $timersConfig;

    /** @var int */
    private static int $sotwEndAt = 0;

    public static function load(): void {
        // Initialize the composer autoload
        /*if (!is_file($bootstrap = 'phar://' . Server::getInstance()->getPluginPath() . ($instance = HCFCore::getInstance())->getName() . '.phar/vendor/autoload.php')) {
            $instance->getLogger()->error('Composer autoloader not found at ' . $bootstrap);
            $instance->getLogger()->warning('Please install/update Composer dependencies or use provided build.');

            exit(1);
        }

        require_once($bootstrap);*/

        self::$placeHolders = (new Config(HCFCore::getInstance()->getDataFolder() . 'messages.yml'))->getAll();

        self::$timersConfig = new Config(HCFCore::getInstance()->getDataFolder() . 'timers.yml');

        self::setSotwTime(HCFCore::getConfigInt('map.sotw-remaining'), false);
    }

    /**
     * @param string $text
     * @param string[] $args
     *
     * @return string
     */
    public static function replacePlaceholders(string $text, array $args = []): string {
        $text = self::$placeHolders[$text] ?? $text;

        if (is_array($text)) {
            return self::replacePlaceholders(implode("\n", $text), $args);
        }

        foreach ($args as $i => $arg) {
            $text = str_replace('<' . $i . '>', strval($arg), $text);
        }

        return TextFormat::colorize($text);
    }

    /**
     * @param int $timestamp
     *
     * @return string
     */
    public static function dateNow(int $timestamp = -1): string {
        return date('Y-m-d H:i:s', ($timestamp === -1 ? time() : $timestamp));
    }

    /**
     * @param int $time
     *
     * @return string
     */
    public static function dateString(int $time): string {
        return $time > 60 ? ($time < 60 * 60 ? gmdate('i:s', $time) : gmdate('H:i:s', $time)) : $time . 's';
    }

    /**
     * @param World $world
     *
     * @return Position
     */
    public static function posZero(World $world): Position {
        return Position::fromObject(Vector3::zero(), $world);
    }

    /**
     * @return World
     */
    public static function getDefaultWorld(): World {
        return Server::getInstance()->getWorldManager()->getDefaultWorld() ?? throw new UnexpectedException('Default world not was loaded...');
    }

    /**
     * @param int  $time
     * @param bool $overwrite
     */
    public static function setSotwTime(int $time, bool $overwrite): void {
        self::$sotwEndAt = time() + $time;

        if (!$overwrite) return;

        try {
            $config = HCFCore::getInstance()->getConfig();

            $config->setNested('map.sotw-remaining', $time);
            $config->save();
        } catch (Exception $e) {
            Server::getInstance()->getLogger()->logException($e);
        }
    }

    /**
     * @return bool
     */
    public static function isSotwRunning(): bool {
        return self::getSotwTimeRemaining() > 0;
    }

    /**
     * @return int
     */
    public static function getSotwTimeRemaining(): int {
        return self::$sotwEndAt - time();
    }

    /**
     * @param string $xuid
     * @param array  $timersData
     */
    public static function storeProfileTimers(string $xuid, array $timersData): void {
        try {
            self::$timersConfig->set($xuid, $timersData);
            self::$timersConfig->save();
        } catch (Exception $e) {
            Server::getInstance()->getLogger()->logException($e);
        }
    }

    /**
     * @param string $xuid
     *
     * @return array
     */
    public static function fetchProfileTimers(string $xuid): array {
        return is_array($stored = self::$timersConfig->get($xuid)) ? $stored : [];
    }
}