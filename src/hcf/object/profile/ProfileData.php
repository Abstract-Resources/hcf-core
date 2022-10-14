<?php

declare(strict_types=1);

namespace hcf\object\profile;

final class ProfileData {

    /**
     * @param string $xuid
     * @param string $name
     * @param int    $factionId
     * @param int    $kills
     * @param int    $deaths
     * @param bool   $joinedBefore
     */
    public function __construct(
        private string $xuid,
        private string $name,
        private int $factionId,
        private int $kills,
        private int $deaths,
        private bool $joinedBefore
    ) {}

    /**
     * @return string
     */
    public function getXuid(): string {
        return $this->xuid;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getFactionId(): int {
        return $this->factionId;
    }

    /**
     * @return int
     */
    public function getDeaths(): int {
        return $this->deaths;
    }

    /**
     * @return int
     */
    public function getKills(): int {
        return $this->kills;
    }

    /**
     * @return bool
     */
    public function hasJoinedBefore(): bool {
        return $this->joinedBefore;
    }
}