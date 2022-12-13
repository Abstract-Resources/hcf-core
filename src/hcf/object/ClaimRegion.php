<?php

declare(strict_types=1);

namespace hcf\object;

use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;

final class ClaimRegion {

    public const BLOCK_BREAK_FLAG = 'block_break_disabled';
    public const BLOCK_PLACE_FLAG = 'block_place_disabled';
    public const ENTITY_DAMAGE_FLAG = 'entity_damage_disabled';

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
    public function isFlagEnabled(string $flagName): bool {
        return $this->flags[$flagName] ?? false;
    }

    /**
     * @return bool
     */
    public function isDeathBan(): bool {
        return !$this->isFlagEnabled(self::ENTITY_DAMAGE_FLAG);
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