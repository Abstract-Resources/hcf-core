<?php

declare(strict_types=1);

namespace hcf\object;

use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\Position;

final class ClaimRegion {

    /** @var array<string, ClaimCuboid> */
    private static array $claimingSessions = [];

    /**
     * @param string      $name
     * @param ClaimCuboid $cuboid
     */
    public function __construct(
        private string $name,
        private ClaimCuboid $cuboid
    ) {}

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return ClaimCuboid
     */
    public function getCuboid(): ClaimCuboid {
        return $this->cuboid;
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