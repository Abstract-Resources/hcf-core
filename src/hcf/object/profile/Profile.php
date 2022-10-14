<?php

declare(strict_types=1);

namespace hcf\object\profile;

final class Profile {

    /**
     * @param string $xuid
     * @param string $name
     * @param int    $factionId
     * @param int    $kills
     * @param int    $deaths
     */
    public function __construct(
        private string $xuid,
        private string $name,
        private int $factionId = 0,
        private int $kills = 0,
        private int $deaths = 0
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
    public function getKills(): int {
        return $this->kills;
    }

    /**
     * @return int
     */
    public function getDeaths(): int {
        return $this->deaths;
    }
}