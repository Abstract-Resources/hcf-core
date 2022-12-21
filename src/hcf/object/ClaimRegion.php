<?php

declare(strict_types=1);

namespace hcf\object;

use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use function in_array;

final class ClaimRegion {

    public const DISALLOW_BLOCK_BREAK = 'block_break_disabled';
    public const DISALLOW_BLOCK_PLACE = 'block_place_disabled';
    public const DISALLOW_ENTITY_DAMAGE = 'entity_damage_disabled';
    public const KOTH = 'koth';

    /** @var array<string, ClaimCuboid> */
    private static array $claimingSessions = [];

    /**
     * @param string              $name
     * @param ClaimCuboid         $cuboid
     * @param array<string, bool> $flags
     */
    public function __construct(
        private string $name,
        private ClaimCuboid $cuboid,
        private array $flags = []
    ) {}

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getCustomName(): string {
        return ($this->isDeathBan() ? TextFormat::RED : TextFormat::GREEN) . $this->name;
    }

    /**
     * @return ClaimCuboid
     */
    public function getCuboid(): ClaimCuboid {
        return $this->cuboid;
    }

    /**
     * @param string $flagName
     *
     * @return bool
     */
    public function hasFlag(string $flagName): bool {
        return in_array($flagName, $this->flags, true);
    }

    /**
     * @return bool
     */
    public function isDeathBan(): bool {
        return !$this->hasFlag(self::DISALLOW_ENTITY_DAMAGE);
    }

    /**
     * @param Player $player
     *
     * @return ClaimCuboid|null
     */
    public static function getIfClaiming(Player $player): ?ClaimCuboid {
        return self::$claimingSessions[$player->getXuid()] ?? null;
    }

    /**
     * @param Player $player
     */
    public static function store(Player $player): void {
        self::$claimingSessions[$player->getXuid()] = new ClaimCuboid(
            Position::fromObject(Vector3::zero(), $player->getWorld()),
            Position::fromObject(Vector3::zero(), $player->getWorld())
        );
    }

    /**
     * @param string $xuid
     */
    public static function flush(string $xuid): void {
        unset(self::$claimingSessions[$xuid]);
    }
}