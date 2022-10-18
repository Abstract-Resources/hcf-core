<?php

declare(strict_types=1);

namespace hcf\object\profile;

use hcf\object\profile\query\SaveProfileQuery;
use hcf\thread\ThreadPool;

final class Profile {

	/** @var bool */
	private bool $alreadySaving = false;

    /**
     * @param string      $xuid
     * @param string      $name
     * @param string|null $factionId
     * @param int         $factionRole
     * @param int         $kills
     * @param int         $deaths
     */
	public function __construct(
		private string $xuid,
		private string $name,
		private ?string $factionId = null,
        private int $factionRole = ProfileData::MEMBER_ROLE,
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
	 * @return string|null
	 */
	public function getFactionId(): ?string {
		return $this->factionId;
	}

    /**
     * @param string|null $factionId
     */
    public function setFactionId(?string $factionId): void {
        $this->factionId = $factionId;
    }

    /**
     * @return int
     */
    public function getFactionRole(): int {
        return $this->factionRole;
    }

    /**
     * @param int $factionRole
     */
    public function setFactionRole(int $factionRole): void {
        $this->factionRole = $factionRole;
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

	/**
	 * @return bool
	 */
	public function isAlreadySaving(): bool {
		return $this->alreadySaving;
	}

	/**
	 * @param bool $alreadySaving
	 */
	public function setAlreadySaving(bool $alreadySaving): void {
		$this->alreadySaving = $alreadySaving;
	}

	/**
	 * @param bool $joinedBefore
	 */
	public function forceSave(bool $joinedBefore): void {
		$this->alreadySaving = true;

		ThreadPool::getInstance()->submit(new SaveProfileQuery(new ProfileData(
			$this->xuid,
			$this->name,
			$this->factionId,
			$this->kills,
			$this->deaths,
			$joinedBefore
		)));
	}
}