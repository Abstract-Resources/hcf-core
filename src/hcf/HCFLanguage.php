<?php

declare(strict_types=1);

namespace hcf;

use hcf\utils\HCFUtils;
use pocketmine\utils\EnumTrait;

/**
 * @method static HCFLanguage YOU_ALREADY_IN_FACTION()
 * @method static HCFLanguage FACTION_ALREADY_EXISTS()
 * @method static HCFLanguage COMMAND_FACTION_NOT_IN()
 * @method static HCFLanguage COMMAND_FACTION_NOT_LEADER()
 */
final class HCFLanguage {
    use EnumTrait {
        __construct as Enum___construct;
    }

    /**
     * Inserts default entries into the registry.
     *
     * (This ought to be private, but traits suck too much for that.)
     */
    protected static function setup(): void {
        self::registerAll(
            new HCFLanguage('YOU_ALREADY_IN_FACTION'),
            new HCFLanguage('FACTION_ALREADY_EXISTS', ['faction']),
            new HCFLanguage('COMMAND_FACTION_NOT_IN'),
            new HCFLanguage('COMMAND_FACTION_NOT_LEADER')
        );
    }

    public function __construct(private string $key, private array $parameters = []) {
        $this->Enum___construct($this->key);
    }

    /**
     * @param string ...$args
     *
     * @return string
     */
    public function build(string...$args): string {
        $parameters = [];

        foreach ($args as $i => $arg) $parameters[$this->parameters[$i]] = $arg;

        return HCFUtils::replacePlaceholders($this->key, ...$parameters);
    }
}