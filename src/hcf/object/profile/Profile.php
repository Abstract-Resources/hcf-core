<?php

declare(strict_types=1);

namespace hcf\object\profile;

use hcf\object\ClaimRegion;
use hcf\object\profile\query\SaveProfileQuery;
use hcf\thread\ThreadPool;
use hcf\utils\HCFUtils;
use pocketmine\player\Player;
use pocketmine\Server;

final class Profile {

	/** @var bool */
	private bool $alreadySaving = false;

    /** @var ClaimRegion */
    private ClaimRegion $claimRegion;

    /**
     * @param string      $xuid
     * @param string      $name
     * @param string      $firstSeen
     * @param string      $lastSeen
     * @param string|null $factionId
     * @param int         $factionRole
     * @param int         $kills
     * @param int         $deaths
     * @param int         $balance
     */
	public function __construct(
		private string $xuid,
		private string $name,
        private string $firstSeen,
        private string $lastSeen,
        private ?string $factionId = null,
        private int $factionRole = ProfileData::MEMBER_ROLE,
        private int $kills = 0,
        private int $deaths = 0,
        private int $balance = 0,
	) {}

    /**
     * @return Player|null
     */
    public function getInstance(): ?Player {
        return Server::getInstance()->getPlayerExact($this->name);
    }

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
     * @return int
     */
    public function getBalance(): int {
        return $this->balance;
    }

    /**
     * @param int $balance
     */
    public function setBalance(int $balance): void {
        $this->balance = $balance;
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
     * @param ClaimRegion $claimRegion
     */
    public function setClaimRegion(ClaimRegion $claimRegion): void {
        $this->claimRegion = $claimRegion;
    }

    /**
     * @return ClaimRegion
     */
    public function getClaimRegion(): ClaimRegion {
        return $this->claimRegion;
    }

    /**
     * @param bool $joinedBefore
     * @param bool $stored
     */
	public function forceSave(bool $joinedBefore, bool $stored = true): void {
		$this->alreadySaving = true;

		ThreadPool::getInstance()->submit(new SaveProfileQuery(new ProfileData(
			$this->xuid,
			$this->name,
			$this->factionId,
            $this->factionRole,
			$this->kills,
			$this->deaths,
            $this->firstSeen,
            $stored ? HCFUtils::dateNow() : $this->lastSeen,
			$joinedBefore
		)));
	}

    /**
     * @param ProfileData $profileData
     *
     * @return Profile
     */
    public static function fromProfileData(ProfileData $profileData): Profile {
        return new self (
            $profileData->getXuid(),
            $profileData->getName(),
            $profileData->getFirstSeen(),
            $profileData->getLastSeen(),
            $profileData->getFactionId(),
            $profileData->getFactionRole(),
            $profileData->getKills(),
            $profileData->getDeaths()
        );
    }
}