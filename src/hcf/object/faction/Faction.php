<?php

declare(strict_types=1);

namespace hcf\object\faction;

use hcf\object\faction\query\SaveFactionQuery;
use hcf\thread\ThreadPool;
use function strtolower;

final class Faction {

    /** @var array<string, FactionMember> */
    private array $members = [];
    /** @var array<string, string> */
    private array $membersXuid = [];

    /**
     * @param string $id
     * @param string $name
     * @param float  $deathsUntilRaidable
     * @param int    $regenCooldown
     * @param int    $lastDtrUpdate
     * @param int    $balance
     * @param int    $points
     */
	public function __construct(
		private string $id,
		private string $name,
        private float $deathsUntilRaidable,
        private int $regenCooldown,
        private int $lastDtrUpdate,
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
	 * @param string $name
	 */
	public function setName(string $name): void {
		$this->name = $name;
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
    public function getLastDtrUpdate(): int {
        return $this->lastDtrUpdate;
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

    /**
     * @param string $xuid
     * @param string $name
     * @param int    $role
     */
    public function registerMember(string $xuid, string $name, int $role): void {
        $this->members[$xuid] = new FactionMember(
            $xuid,
            $name,
            $role
        );

        $this->membersXuid[strtolower($name)] = $xuid;
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
	 * @param bool $exists
	 */
	public function forceSave(bool $exists): void {
        ThreadPool::getInstance()->submit(new SaveFactionQuery(new FactionData()));
	}
}