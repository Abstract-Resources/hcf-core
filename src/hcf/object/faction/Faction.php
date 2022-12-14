<?php

declare(strict_types=1);

namespace hcf\object\faction;

use hcf\factory\FactionFactory;
use hcf\object\faction\query\SaveFactionQuery;
use hcf\thread\ThreadPool;
use pocketmine\entity\Location;
use pocketmine\Server;
use function abs;
use function array_diff;
use function count;
use function in_array;
use function min;
use function round;
use function strtolower;
use function time;

final class Faction {

    /** @var array<string, FactionMember> */
    private array $members = [];
    /** @var array<string, string> */
    private array $membersXuid = [];
    /** @var array */
    private array $pendingInvitesSent = [];

    /** @var bool */
    private bool $open = false; // This is a feature

    /**
     * @param string        $id
     * @param string        $name
     * @param string        $leaderXuid
     * @param float         $deathsUntilRaidable
     * @param int           $regenCooldown
     * @param int           $lastDtrUpdate
     * @param int           $balance
     * @param int           $points
     * @param Location|null $hqLocation
     */
	public function __construct(
		private string $id,
		private string $name,
        private string $leaderXuid,
        private float $deathsUntilRaidable,
        private int $regenCooldown,
        private int $lastDtrUpdate,
        private int $balance,
        private int $points,
        private ?Location $hqLocation = null
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
	 * @param string $name
	 */
	public function setName(string $name): void {
		$this->name = $name;
	}

    /**
     * @return string
     */
    public function getLeaderXuid(): string {
        return $this->leaderXuid;
    }

    /**
     * @param bool $updateLastCheck
     *
     * @return float
     */
    public function getDeathsUntilRaidable(bool $updateLastCheck = false): float {
        if ($updateLastCheck) $this->updateDeathsUntilRaidable();

        return $this->deathsUntilRaidable;
    }

    private function updateDeathsUntilRaidable(): void {
        if ($this->getRegenStatus() !== FactionData::STATUS_REGENERATING) return;

        $timePassed = ($now = time()) - $this->getLastDtrUpdate();

        if ($timePassed < ($dtrUpdate = FactionFactory::getInstance()->getDtrUpdate())) {
            return;
        }

        $multiplier = ($timePassed + ($timePassed % $dtrUpdate)) / $dtrUpdate;
        $this->setDeathsUntilRaidable($this->deathsUntilRaidable + ($multiplier * FactionFactory::getInstance()->getDtrIncrementBetweenUpdate()));

        $this->lastDtrUpdate = $now;
    }

    /**
     * @param float $deathsUntilRaidable
     * @param bool  $limit
     *
     * @return float
     */
    public function setDeathsUntilRaidable(float $deathsUntilRaidable, bool $limit = true): float {
        $deathsUntilRaidable = round($deathsUntilRaidable * 100.0, 1) / 100.0;

        if ($limit) $deathsUntilRaidable = min($deathsUntilRaidable, $this->getMaximumDeathsUntilRaidable());

        if (abs($deathsUntilRaidable - $this->getDeathsUntilRaidable()) !== 0.0) {
            $this->deathsUntilRaidable = round($deathsUntilRaidable * 100.0) / 100.0;

            $this->lastDtrUpdate = time();

            $this->forceSave(true);
        }

        return $this->deathsUntilRaidable;
    }

    /**
     * @return float
     */
    public function getMaximumDeathsUntilRaidable(): float {
        return count($this->members) === 1 ? 1.1 : min(
            FactionFactory::getInstance()->getMaxDeathsUntilRaidable(),
            count($this->members) * FactionFactory::getInstance()->getDtrPerPlayer()
        );
    }

    /**
     * @param int $time
     */
    public function setRemainingRegenerationTime(int $time): void {
        $this->regenCooldown = time() + $time;

        $this->lastDtrUpdate = $this->regenCooldown;
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
    public function getLastDtrUpdate(): int {
        return $this->lastDtrUpdate;
    }

    /**
     * @return int
     */
    public function getRemainingRegenerationTime(): int {
        return $this->regenCooldown === 0 ? 0 : $this->regenCooldown - time();
    }

    /**
     * @return int
     */
    public function getRegenStatus(): int {
        if ($this->getRemainingRegenerationTime() > 0) return FactionData::STATUS_PAUSED;

        if ($this->getMaximumDeathsUntilRaidable() > $this->getDeathsUntilRaidable()) return FactionData::STATUS_REGENERATING;

        return FactionData::STATUS_FULL;
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
     * @return int
     */
    public function getPoints(): int {
        return $this->points;
    }

    /**
     * @param int $points
     */
    public function setPoints(int $points): void {
        $this->points = $points;
    }

    /**
     * @param string $xuid
     * @param string $name
     * @param int    $role
     * @param int    $kills
     */
    public function registerMember(string $xuid, string $name, int $role, int $kills): void {
        $this->members[$xuid] = new FactionMember(
            $xuid,
            $name,
            $role,
            $kills
        );

        $this->membersXuid[strtolower($name)] = $xuid;
    }

    /**
     * @param string $xuid
     * @param string $name
     */
    public function flushMember(string $xuid, string $name): void {
        unset($this->membersXuid[$name], $this->members[$xuid]);
    }

    /**
     * @param string $name
     *
     * @return FactionMember|null
     */
    public function getMemberUsingName(string $name): ?FactionMember {
        if (($xuid = $this->membersXuid[strtolower($name)] ?? null) === null) {
            return null;
        }

        return $this->getMember($xuid);
    }

    /**
     * @param string $xuid
     *
     * @return FactionMember|null
     */
    public function getMember(string $xuid): ?FactionMember {
        return $this->members[$xuid] ?? null;
    }

    /**
     * @return FactionMember[]
     */
    public function getMembers(): array {
        return $this->members;
    }

    /**
     * @return bool
     */
    public function isOpen(): bool {
        return $this->open;
    }

    /**
     * @param string $xuid
     */
    public function addPendingInvite(string $xuid): void {
        $this->pendingInvitesSent[] = $xuid;
    }

    /**
     * @param string $xuid
     *
     * @return bool
     */
    public function hasPendingInvite(string $xuid): bool {
        return in_array($xuid, $this->pendingInvitesSent, true);
    }

    /**
     * @param string $xuid
     */
    public function removePendingInvite(string $xuid): void {
        $this->pendingInvitesSent = array_diff($this->pendingInvitesSent, [$xuid]);
    }

    /**
     * @return Location|null
     */
    public function getHqLocation(): ?Location {
        return $this->hqLocation;
    }

    /**
     * @param Location|null $hqLocation
     */
    public function setHqLocation(?Location $hqLocation): void {
        $this->hqLocation = $hqLocation;
    }

    /**
     * @param string $message
     */
    public function broadcastMessage(string $message): void {
        foreach ($this->getMembers() as $factionMember) {
            if (($instance = Server::getInstance()->getPlayerExact($factionMember->getName())) === null) continue;

            $instance->sendMessage($message);
        }
    }

	/**
	 * @param bool $exists
	 */
	public function forceSave(bool $exists): void {
        ThreadPool::getInstance()->submit(new SaveFactionQuery(new FactionData(
            $this->id,
            $this->name,
            $this->leaderXuid,
            $this->deathsUntilRaidable,
            $this->regenCooldown,
            $this->balance,
            $this->points
        ), $exists));
	}
}