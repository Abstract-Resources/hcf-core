<?php

declare(strict_types=1);

namespace hcf\object\profile\query;

use hcf\object\profile\ProfileData;
use hcf\thread\query\Query;
use hcf\utils\MySQL;

final class SaveProfileQuery extends Query {

    /**
     * @param ProfileData $profileData
     */
    public function __construct(private ProfileData $profileData) {}

    /**
     * @param MySQL $provider
     */
    public function run(MySQL $provider): void {
        if (!$this->profileData->hasJoinedBefore()) {
            $provider->prepareStatement('INSERT INTO profiles (xuid, username, faction_id, kills, deaths, first_seen, last_seen) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $provider->set(
                $this->profileData->getXuid(),
                $this->profileData->getName(),
                $this->profileData->getFactionId(),
                $this->profileData->getKills(),
                $this->profileData->getDeaths(),
                0,
                0
            );
        } else {
            $provider->prepareStatement('UPDATE profiles SET username = ?, faction_id = ?, kills = ?, deaths = ?, first_seen = ?, last_seen = ? WHERE xuid = ?');
            $provider->set(
                $this->profileData->getName(),
                $this->profileData->getFactionId(),
                $this->profileData->getKills(),
                $this->profileData->getDeaths(),
                0,
                0,
                $this->profileData->getXuid()
            );
        }

        $provider->executeStatement()->close();
    }

    public function onComplete(): void {
        // TODO: Implement onComplete() method.
    }
}