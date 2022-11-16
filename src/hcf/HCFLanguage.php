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
 * @method static HCFLanguage COMMAND_FACTION_ATTEMPT_JOIN()
 * @method static HCFLanguage PLAYER_NOT_FOUND()
 * @method static HCFLanguage YOU_CANT_USE_THIS_ON_YOURSELF()
 * @method static HCFLanguage PLAYER_IN_FACTION()
 * @method static HCFLanguage PLAYER_ALREADY_INVITED()
 * @method static HCFLanguage FACTION_INVITATION_SENT()
 * @method static HCFLanguage FACTION_INVITE_RECEIVED()
 * @method static HCFLanguage FACTION_NOT_INVITED()
 * @method static HCFLanguage YOU_CANT_CLAIM_HERE()
 * @method static HCFLanguage PLAYER_CLAIM_POSITION()
 * @method static HCFLanguage PLAYER_CLAIM_COST()
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
            new HCFLanguage('COMMAND_FACTION_NOT_LEADER'),
            new HCFLanguage('COMMAND_FACTION_ATTEMPT_JOIN'),
            new HCFLanguage('PLAYER_NOT_FOUND', ['player']),
            new HCFLanguage('YOU_CANT_USE_THIS_ON_YOURSELF'),
            new HCFLanguage('PLAYER_IN_FACTION', ['player']),
            new HCFLanguage('PLAYER_ALREADY_INVITED', ['player']),
            new HCFLanguage('FACTION_INVITATION_SENT', ['player', 'sender']),
            new HCFLanguage('FACTION_INVITE_RECEIVED', ['player', 'faction']),
            new HCFLanguage('FACTION_NOT_INVITED', ['faction']),
            new HCFLanguage('YOU_CANT_CLAIM_HERE'),
            new HCFLanguage('PLAYER_CLAIM_POSITION', ['corner', 'x', 'y', 'z']),
            new HCFLanguage('PLAYER_CLAIM_COST', ['cost', 'x', 'z', 'blocks'])
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