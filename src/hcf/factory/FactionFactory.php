<?php

declare(strict_types=1);

namespace hcf\factory;

use hcf\HCFCore;
use hcf\object\ClaimCuboid;
use hcf\object\ClaimRegion;
use hcf\object\faction\Faction;
use hcf\object\faction\query\DisbandFactionQuery;
use hcf\object\profile\Profile;
use hcf\object\profile\ProfileData;
use hcf\object\profile\query\BatchSaveProfileQuery;
use hcf\thread\ThreadPool;
use hcf\utils\HCFUtils;
use hcf\utils\UnexpectedException;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\format\Chunk;
use pocketmine\world\Position;
use pocketmine\world\World;
use function array_values;
use function count;
use function is_array;
use function is_int;
use function strtolower;
use function time;

final class FactionFactory {
	use SingletonTrait;

    /** @var array<string, Faction> */
	private array $factions = [];
    /** @var array<string, string> */
    private array $factionsId = [];
    /** @var array<int, array<string, ClaimRegion>> */
    private array $claimsPerChunk = [];
    /** @var array<string, ClaimRegion> */
    private array $adminClaims = [];
    /** @var array<string, ClaimRegion> */
    private array $factionClaim = [];
    /** @var array<string, int> */
    private array $factionsRegenerating = [];

    public function init(): void {
        HCFCore::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (): void {
            foreach ($this->factionsRegenerating as $factionId => $time) {
                if ($time >= time()) continue;

                if (($faction = $this->getFaction($factionId)) !== null) {
                    $faction->getDeathsUntilRaidable(true); // Update the dtr
                    $faction->forceSave(true);
                }

                unset($this->factionsRegenerating[$factionId]);
            }
        }), 60);

        if (!is_array($claims = HCFCore::getInstance()->getConfig()->getNested('map.admin_claims'))) return;

        foreach ($claims as $claimName => $storage) {
            if (($value = $storage['value'] ?? null) === null) continue;

            $cuboid = null;

            if (is_array($value)) {
                $cuboid = ClaimCuboid::fromStorage($value);
            } elseif (is_int($value)) {
                $cuboid = ClaimCuboid::fromNumber($value);
            }

            if ($cuboid === null) continue;

            $this->adminClaims[$claimName] = new ClaimRegion($claimName, $cuboid, $storage['flags'] ?? []);
        }
    }

    /**
     * @param Profile $profile
     * @param Faction $faction
     * @param int     $role
     */
    public function joinFaction(Profile $profile, Faction $faction, int $role): void {
        $profile->setFactionId($faction->getId());
        $profile->setFactionRole($role);

        $profile->forceSave(true);

        $faction->registerMember($profile->getXuid(), $profile->getName(), $role);
    }

    /**
     * @param Faction $faction
     */
    public function disbandFaction(Faction $faction): void {
        if (($leader = $faction->getMember($faction->getLeaderXuid())) === null) {
            throw new UnexpectedException('An unexpected error as occurred while get the Faction leader!');
        }

        $offlineProfiles = [];

        foreach ($faction->getMembers() as $factionMember) {
            if (($profile = ProfileFactory::getInstance()->getIfLoaded($factionMember->getXuid())) === null) {
                // TODO: Store all offline profiles and update it on a query

                $offlineProfiles[] = new ProfileData(
                    $factionMember->getXuid(),
                    $factionMember->getName(),
                    null,
                    ProfileData::MEMBER_ROLE,
                    -1,
                    -1,
                    -1,
                    HCFUtils::dateNow(),
                    HCFUtils::dateNow(),
                    true
                );

                continue;
            }

            $profile->setFactionId(null);
            $profile->setFactionRole(ProfileData::MEMBER_ROLE);

            $profile->forceSave(true);

            if (($instance = $profile->getInstance()) === null) continue;

            $instance->sendMessage(HCFUtils::replacePlaceholders('LEADER_DISBANDED_THE_FACTION', ['player' => $leader->getName()]));
        }

        ThreadPool::getInstance()->submit(new BatchSaveProfileQuery($offlineProfiles));
        ThreadPool::getInstance()->submit(new DisbandFactionQuery($faction->getId()));

        $this->flushClaim($faction);

        unset($this->factions[$faction->getId()], $this->factionsId[$faction->getName()], $this->factionsRegenerating[$faction->getId()]);
    }

    /**
     * @param Faction $faction
     */
    public function registerFaction(Faction $faction): void {
        $this->factions[$faction->getId()] = $faction;

        $this->factionsId[$faction->getName()] = $faction->getId();
    }

    /**
     * @param ClaimRegion $claimRegion
     * @param string      $factionId
     */
    public function registerClaim(ClaimRegion $claimRegion, string $factionId): void {
        $cuboid = $claimRegion->getCuboid();

        for ($x = $cuboid->getFirstCorner()->getFloorX() >> Chunk::COORD_BIT_SIZE; $x <= $cuboid->getSecondCorner()->getFloorX() >> Chunk::COORD_BIT_SIZE; $x++) {
            for ($z = $cuboid->getFirstCorner()->getFloorZ() >> Chunk::COORD_BIT_SIZE; $z <= $cuboid->getSecondCorner()->getFloorZ() >> Chunk::COORD_BIT_SIZE; $z++) {
                $this->claimsPerChunk[World::chunkHash($x, $z)][$factionId] = $claimRegion;
            }
        }

        $this->factionClaim[$factionId] = $claimRegion;
    }

    /**
     * @param Faction $faction
     */
    public function flushClaim(Faction $faction): void {
        if (($claimRegion = $this->getFactionClaim($faction)) === null) return;

        $factionId = $faction->getId();
        $cuboid = $claimRegion->getCuboid();

        for ($x = $cuboid->getFirstCorner()->getFloorX() >> Chunk::COORD_BIT_SIZE; $x <= $cuboid->getSecondCorner()->getFloorX() >> Chunk::COORD_BIT_SIZE; $x++) {
            for ($z = $cuboid->getFirstCorner()->getFloorZ() >> Chunk::COORD_BIT_SIZE; $z <= $cuboid->getSecondCorner()->getFloorZ() >> Chunk::COORD_BIT_SIZE; $z++) {
                $claimsAt = $this->claimsPerChunk[World::chunkHash($x, $z)] ?? [];

                if (count($claimsAt) <= 0) continue;

                unset($claimsAt[$factionId]);
            }
        }

        unset($this->factionClaim[$factionId]);
    }

    /**
     * @param Faction $faction
     *
     * @return ClaimRegion|null
     */
    public function getFactionClaim(Faction $faction): ?ClaimRegion {
        return $this->factionClaim[$faction->getId()] ?? null;
    }

	/**
	 * @param string $factionName
	 *
	 * @return Faction|null
	 */
	public function getFactionName(string $factionName): ?Faction {
        if (($id = $this->factionsId[strtolower($factionName)] ?? null) === null) {
            return null;
        }

		return $this->factions[$id] ?? null;
	}

    /**
     * @param string $id
     *
     * @return Faction|null
     */
    public function getFaction(string $id): ?Faction {
        return $this->factions[$id] ?? null;
    }

    /**
     * @param Player $player
     *
     * @return Faction|null
     */
    public function getPlayerFaction(Player $player): ?Faction {
        if (($profile = ProfileFactory::getInstance()->getIfLoaded($player->getXuid())) === null) return null;
        if (($factionId = $profile->getFactionId()) === null) return null;

        return $this->getFaction($factionId);
    }

    /**
     * @param Position $position
     *
     * @return Faction|null
     */
    public function getFactionAt(Position $position): ?Faction {
        return $this->getFactionName($this->getRegionAt($position)->getName());
    }

    /**
     * @return Faction[]
     */
    public function getFactionsStored(): array {
        return array_values($this->factions);
    }

    /**
     * @param Position $position
     *
     * @return ClaimRegion
     */
    public function getRegionAt(Position $position): ClaimRegion {
        /** @var ClaimRegion[] $claimsPerChunk */
        $claimsPerChunk = $this->claimsPerChunk[World::chunkHash($position->getFloorX() >> Chunk::COORD_BIT_SIZE, $position->getFloorZ() >> Chunk::COORD_BIT_SIZE)] ?? [];

        foreach ($claimsPerChunk as $claimRegion) {
            if (!$claimRegion->getCuboid()->isInside($position)) continue;

            return $claimRegion;
        }

        return $this->adminClaims[HCFUtils::REGION_WILDERNESS] ?? throw new UnexpectedException('Region \'Wilderness\' not found...');
    }

    /**
     * @param Position $position
     *
     * @return bool
     */
    public function isInsideSpawn(Position $position): bool {
        return ($claimRegion = $this->adminClaims[HCFUtils::REGION_SPAWN] ?? null) !== null && $claimRegion->getCuboid()->isInside($position);
    }

    /**
     * @param string $factionId
     * @param int    $targetTime
     */
    public function storeFactionRegenerating(string $factionId, int $targetTime): void {
        $this->factionsRegenerating[$factionId] = $targetTime + 2;
    }

    /**
     * @return int
     */
    public function getDtrUpdate(): int {
        return HCFCore::getConfigInt('factions.dtr-regen-time');
    }

    /**
     * @return float
     */
    public function getDtrIncrementBetweenUpdate(): float {
        return HCFCore::getConfigFloat('factions.dtr-increment'); // Im fan of large config setting
    }

    /**
     * @return int
     */
    public function getDtrFreeze(): int {
        return HCFCore::getConfigInt('factions.dtr-freeze');
    }

    /**
     * @return float
     */
    public function getDtrPerPlayer(): float {
        return HCFCore::getConfigInt('factions.dtr-per-player');
    }

    /**
     * @return int
     */
    public function getMaxDeathsUntilRaidable(): int {
        return HCFCore::getConfigInt('factions.max-dtr');
    }
}