<?php

declare(strict_types=1);

namespace hcf\object\faction;

use hcf\factory\ProfileFactory;
use hcf\object\profile\Profile;

final class FactionMember {

    /**
     * @param string $xuid
     * @param string $name
     * @param int    $role
     * @param int    $kills
     */
    public function __construct(
        private string $xuid,
        private string $name,
        private int $role,
        private int $kills
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
        if (($profile = $this->getProfile()) !== null) {
            return $profile->getName();
        }

        return $this->name;
    }

    /**
     * @return int
     */
    public function getRole(): int {
        if (($profile = $this->getProfile()) !== null) {
            return $profile->getFactionRole();
        }

        return $this->role;
    }

    /**
     * @return int
     */
    public function getKills(): int {
        if (($profile = $this->getProfile()) !== null) {
            return $profile->getKills();
        }

        return $this->kills;
    }

    /**
     * @return Profile|null
     */
    private function getProfile(): ?Profile {
        return ProfileFactory::getInstance()->getPlayerProfile($this->name);
    }

    /**
     * @return bool
     */
    public function isOnline(): bool {
        return $this->getProfile() !== null;
    }
}