<?php

declare(strict_types=1);

namespace hcf\object\profile\query;

use hcf\factory\ProfileFactory;
use hcf\object\profile\ProfileData;
use hcf\thread\datasource\MySQL;
use hcf\thread\datasource\Query;

final class SaveProfileQuery implements Query {

	/**
	 * @param ProfileData $profileData
	 */
	public function __construct(private ProfileData $profileData) {}

    /**
     * @param MySQL $provider
     *
     * This function is executed on other Thread to prevent lag spike on Main thread
     */
    public function run(MySQL $provider): void {
        self::push($this->profileData, $provider);
	}

    /**
     * @param ProfileData $profileData
     * @param MySQL       $provider
     */
    public static function push(ProfileData $profileData, MySQL $provider): void {
        if ($profileData->hasJoinedBefore()) {
            $provider->prepareStatement('UPDATE profiles SET username = ?, faction_id = ?, faction_role = ?, kills = ?, deaths = ?, lives = ?, balance = ?, first_seen = ?, last_seen = ? WHERE xuid = ?');
        } else {
            $provider->prepareStatement('INSERT INTO profiles (username, faction_id, faction_role, kills, deaths, lives, balance, first_seen, last_seen, xuid) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        }

        $provider->set(
            $profileData->getName(),
            $profileData->getFactionId(),
            $profileData->getFactionRole(),
            $profileData->getKills(),
            $profileData->getDeaths(),
            0,
            $profileData->getBalance(),
            $profileData->getFirstSeen(),
            $profileData->getLastSeen(),
            $profileData->getXuid()
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