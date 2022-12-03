<?php

declare(strict_types=1);

namespace hcf\utils;

use hcf\HCFCore;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use pocketmine\world\World;
use function count;
use function date;
use function gmdate;
use function implode;
use function is_array;
use function str_replace;
use function time;

final class HCFUtils {

    public const REGION_SPAWN = 'Spawn';
    public const REGION_WARZONE = 'Warzone';
    public const REGION_WILDERNESS = 'Wilderness';

    /** @var array */
    private static array $placeHolders = [];

    /**
     * @param string $text
     * @param string[] $args
     *
     * @return string
     */
    public static function replacePlaceholders(string $text, array $args = []): string {
        if (count(self::$placeHolders) === 0) {
            self::$placeHolders = (new Config(HCFCore::getInstance()->getDataFolder() . 'messages.yml'))->getAll();
        }

        $text = self::$placeHolders[$text] ?? $text;

        if (is_array($text)) {
            return self::replacePlaceholders(implode("\n", $text), $args);
        }

        foreach ($args as $i => $arg) {
            if ($arg === '') $arg = 'None';

            $text = str_replace('<' . $i . '>' . ($arg === 'Empty' ? "\n" : ''), $arg === 'Empty' ? '' : $arg, $text);
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
        return $time > 60 ? ($time <= 60 * 60 ? gmdate('i:s', $time) : gmdate('H:i:s', $time)) : $time . 's';
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
}