<?php

declare(strict_types=1);

namespace hcf\factory;

use hcf\object\faction\Faction;
use hcf\object\profile\Profile;
use pocketmine\utils\SingletonTrait;
use function strtolower;

final class FactionFactory {
	use SingletonTrait;

    /** @var array<string, Faction> */
	private array $factions = [];
    /** @var array<string, string> */
    private array $factionsId = [];

	public function init(): void {

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

        if (!isset($this->factions[$faction->getId()])) {
            $this->factions[$faction->getId()] = $faction;

            $this->factionsId[$faction->getName()] = $faction->getId();
        }
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
}