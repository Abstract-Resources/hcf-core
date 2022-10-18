<?php

declare(strict_types=1);

namespace hcf\object\faction;

use hcf\object\faction\query\SaveFactionQuery;
use hcf\thread\ThreadPool;

final class Faction {

    /** @var array<string, FactionMember> */
    private array $members = [];
    /** @var array<string, string> */
    private array $membersXuid = [];

	/**
	 * @param string $id
	 * @param string $name
	 */
	public function __construct(
		private string $id,
		private string $name
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