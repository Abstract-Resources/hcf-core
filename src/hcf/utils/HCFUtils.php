<?php

declare(strict_types=1);

namespace hcf\utils;

use hcf\HCFCore;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use function count;
use function date;
use function implode;
use function is_array;
use function str_replace;
use function time;

final class HCFUtils {

    /** @var array */
    private static array $placeHolders = [];

    /**
     * @param string $text
     * @param string ...$args
     *
     * @return string
     */
    public static function replacePlaceholders(string $text, string...$args): string {
        if (count(self::$placeHolders) === 0) {
            self::$placeHolders = (new Config(HCFCore::getInstance()->getDataFolder() . 'messages.yml'))->getAll();
        }

        $text = self::$placeHolders[$text] ?? $text;

        if (is_array($text)) {
            return self::replacePlaceholders(implode("\n", $text), ...$args);
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
}