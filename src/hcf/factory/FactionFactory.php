<?php

declare(strict_types=1);

namespace hcf\factory;

use hcf\HCFCore;
use hcf\object\faction\Faction;
use hcf\object\profile\Profile;
use hcf\object\profile\ProfileData;
use hcf\object\profile\query\BatchSaveProfileQuery;
use hcf\thread\ThreadPool;
use hcf\utils\HCFUtils;
use hcf\utils\UnexpectedException;
use pocketmine\utils\SingletonTrait;
use function strtolower;

final class FactionFactory {
	use SingletonTrait;

    /** @var array<string, Faction> */
	private array $factions = [];
    /** @var array<string, string> */
    private array $factionsId = [];

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

            $instance->sendMessage(HCFUtils::replacePlaceholders('LEADER_DISBANDED_THE_FACTION', $leader->getName()));
        }

        ThreadPool::getInstance()->submit(new BatchSaveProfileQuery($offlineProfiles));

        // TODO: Run disband faction query

        unset($this->factions[$faction->getId()], $this->factionsId[$faction->getName()]);
    }

    /**
     * @param Faction $faction
     */
    public function registerFaction(Faction $faction): void {
        $this->factions[$faction->getId()] = $faction;

        $this->factionsId[$faction->getName()] = $faction->getId();
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