<?php

declare(strict_types=1);

namespace hcf\object\profile\query;

use hcf\factory\ProfileFactory;
use hcf\object\profile\ProfileData;
use hcf\thread\query\MySQL;
use hcf\thread\query\Query;

final class SaveProfileQuery extends Query {

	/**
	 * @param ProfileData $profileData
	 */
	public function __construct(private ProfileData $profileData) {}

	/**
	 * @param \hcf\thread\query\MySQL $provider
	 *
	 * This function is executed on other Thread to prevent lag spike on Main thread
	 */
	public function run(MySQL $provider): void {
		if ($this->profileData->hasJoinedBefore()) {
			$provider->prepareStatement('UPDATE profiles SET username = ?, faction_id = ?, faction_role = ?, kills = ?, deaths = ?, first_seen = ?, last_seen = ? WHERE xuid = ?');
		} else {
			$provider->prepareStatement('INSERT INTO profiles (username, faction_id, faction_role, kills, deaths, first_seen, last_seen, xuid) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
		}

		$provider->set(
			$this->profileData->getName(),
			$this->profileData->getFactionId(),
            $this->profileData->getFactionRole(),
			$this->profileData->getKills(),
			$this->profileData->getDeaths(),
			$this->profileData->getFirstSeen(),
			$this->profileData->getLastSeen(),
			$this->profileData->getXuid()
		);

		$provider->executeStatement()->close();
	}

	/**
	 * This function is executed on the Main Thread because need use some function of pmmp
	 */
	public function onComplete(): void {
		if (($profile = ProfileFactory::getInstance()->getIfLoaded($this->profileData->getXuid())) === null) return;

		$profile->setAlreadySaving(false);
	}
}