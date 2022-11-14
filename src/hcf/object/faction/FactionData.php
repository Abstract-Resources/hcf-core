<?php

declare(strict_types=1);

namespace hcf\object\faction;

final class FactionData {

    public const STATUS_PAUSED = 0;
    public const STATUS_REGENERATING = 1;
    public const STATUS_FULL = 2;

    /**
     * @param string $id
     * @param string $name
     * @param string $leaderXuid
     * @param float  $deathsUntilRaidable
     * @param int    $regenCooldown
     * @param int    $balance
     * @param int    $points
     */
    public function __construct(
        private string $id,
        private string $name,
        private string $leaderXuid,
        private float $deathsUntilRaidable,
        private int $regenCooldown,
        private int $balance,
        private int $points
    ) {}

    /**
     * @return string
     */
    public function getId(): string {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getLeaderXuid(): string {
        return $this->leaderXuid;
    }

    /**
     * @return float
     */
    public function getDeathsUntilRaidable(): float {
        return $this->deathsUntilRaidable;
    }

    /**
     * @return int
     */
    public function getRegenCooldown(): int {
        return $this->regenCooldown;
    }

    /**
     * @return int
     */
    public function getBalance(): int {
        return $this->balance;
    }

    /**
     * @return int
     */
    public function getPoints(): int {
        return $this->points;
    }
}