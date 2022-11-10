<?php

declare(strict_types=1);

namespace hcf\object\profile;

final class ProfileData {

    public const MEMBER_ROLE = 0;
    public const OFFICER_ROLE = 1;
    public const LEADER_ROLE = 2;

    /**
     * @param string      $xuid
     * @param string      $name
     * @param string|null $factionId
     * @param int         $factionRole
     * @param int         $kills
     * @param int         $deaths
     * @param string      $firstSeen
     * @param string      $lastSeen
     * @param bool        $joinedBefore
     */
	public function __construct(
		private string $xuid,
		private string $name,
		private ?string $factionId,
        private int $factionRole,
		private int $kills,
		private int $deaths,
        private string $firstSeen,
        private string $lastSeen,
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
	 * @return string|null
	 */
	public function getFactionId(): ?string {
		return $this->factionId;
	}

    /**
     * @return int
     */
    public function getFactionRole(): int {
        return $this->factionRole;
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
     * @return string
     */
    public function getFirstSeen(): string {
        return $this->firstSeen;
    }

    /**
     * @return string
     */
    public function getLastSeen(): string {
        return $this->lastSeen;
    }

	/**
	 * @return bool
	 */
	public function hasJoinedBefore(): bool {
		return $this->joinedBefore;
	}

    /**
     * @param int $currentRole
     * @param int $targetRole
     *
     * @return bool
     */
    public static function isAtLeast(int $currentRole, int $targetRole): bool {
        return $currentRole >= $targetRole;
    }
}