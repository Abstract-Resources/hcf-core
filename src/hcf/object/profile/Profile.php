<?php

declare(strict_types=1);

namespace hcf\object\profile;

use hcf\HCFCore;
use hcf\object\ClaimRegion;
use hcf\object\profile\query\SaveProfileQuery;
use hcf\thread\ThreadPool;
use hcf\utils\HCFUtils;
use hcf\utils\ScoreboardBuilder;
use pocketmine\player\Player;
use pocketmine\Server;
use function array_merge;
use function count;
use function in_array;
use function is_array;
use function is_string;
use function str_replace;

final class Profile {

    /** @var array<string, ProfileTimer> */
    private array $timers;

	/** @var bool */
	private bool $alreadySaving = false;

    /** @var ClaimRegion */
    private ClaimRegion $claimRegion;

    private ScoreboardBuilder $scoreboardBuilder;

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

    public function init(): void {
        $this->timers = [
        	ProfileTimer::COMBAT_TAG => new ProfileTimer(ProfileTimer::COMBAT_TAG, 30),
        	ProfileTimer::PVP_TAG => new ProfileTimer(ProfileTimer::PVP_TAG, 60 * 60)
        ];

        foreach (HCFUtils::fetchProfileTimers($this->xuid) as $timerData) {
            $this->toggleProfileTimer($timerData['name'], $timerData['remaining']);
        }

        $this->scoreboardBuilder = new ScoreboardBuilder(
            HCFCore::getConfigString('scoreboard.title'),
            ScoreboardBuilder::SIDEBAR
        );
    }

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
     * @param int $kills
     */
    public function setKills(int $kills): void {
        $this->kills = $kills;
    }

	/**
	 * @return int
	 */
	public function getDeaths(): int {
		return $this->deaths;
	}

    /**
     * @param int $deaths
     */
    public function setDeaths(int $deaths): void {
        $this->deaths = $deaths;
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
     * @param string $name
     * @param int    $countdown
     */
    public function toggleProfileTimer(string $name, int $countdown = -1): void {
        if (($timer = $this->timers[$name] ?? null) === null) return;

        $timer->start($countdown);
    }

    /**
     * @return ProfileTimer[]
     */
    public function getStoredTimers(): array {
        return $this->timers;
    }

    /**
     * @param string $name
     *
     * @return ProfileTimer|null
     */
    public function getProfileTimer(string $name): ?ProfileTimer {
        return $this->timers[$name] ?? null;
    }

    public function showScoreboard(): void {
        if (($instance = $this->getInstance()) === null || !$instance->isConnected()) return;

        $instance->getNetworkSession()->sendDataPacket($this->scoreboardBuilder->addPacket());
    }

    public function updateScoreboard(): void {
        if (!is_array($scoreboardLines = HCFCore::getInstance()->getConfig()->getNested('scoreboard.lines'))) return;
        if (($instance = $this->getInstance()) === null || !$instance->isConnected()) return;

        $allowedPlaceholders = [];
        $args = [
        	'koth_name' => '',
        	'koth_time_remaining' => '',
        	'current_claim' => $this->getClaimRegion()->getName()
        ];

        foreach ($this->timers as $timer) {
            if (($remainingTime = $timer->getRemainingTime()) <= 0) continue;

            $allowedPlaceholders[] = $timer->getName() . '_lines';
            $args['combat_tag_timer'] = HCFUtils::dateString($remainingTime);
        }

        $originalLines = [];
        foreach ($scoreboardLines['default'] as $scoreboardText) {
            if (!is_string($placeholder = str_replace('%', '', $scoreboardText))) continue;
            if (count($placeholderLines = $scoreboardLines[$placeholder] ?? []) <= 0) {
                $originalLines[] = $scoreboardText;

                continue;
            }

            if (!in_array($placeholder, $allowedPlaceholders, true)) continue;

            $originalLines = array_merge($originalLines, $placeholderLines);
        }

        foreach ($originalLines as $line => $scoreboardText) {
            foreach ($this->scoreboardBuilder->fetchLine($line, HCFUtils::replacePlaceholders($scoreboardText, $args)) as $packet) {
                $instance->getNetworkSession()->sendDataPacket($packet);
            }
        }
    }

    public function hideScoreboard(): void {
        if (($instance = $this->getInstance()) === null || !$instance->isConnected()) return;

        $instance->getNetworkSession()->sendDataPacket($this->scoreboardBuilder->removePacket());
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
            $this->balance,
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
            $profileData->getDeaths(),
            $profileData->getBalance()
        );
    }
}