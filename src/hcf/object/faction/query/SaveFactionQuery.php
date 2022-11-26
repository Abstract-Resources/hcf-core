<?php

declare(strict_types=1);

namespace hcf\object\faction\query;

use hcf\object\faction\FactionData;
use hcf\thread\datasource\MySQL;
use hcf\thread\datasource\Query;

final class SaveFactionQuery extends Query {

    /**
     * @param FactionData $factionData
     * @param bool        $exists
     */
    public function __construct(private FactionData $factionData, private bool $exists) {}

    /**
     * @param MySQL $provider
     *
     * This function is executed on other Thread to prevent lag spike on Main thread
     */
    public function run(MySQL $provider): void {
        if ($this->exists) {
            $provider->prepareStatement("UPDATE factions SET fName = ?, leader_xuid = ?, deathsUntilRaidable = ?, regenCooldown = ?, balance = ?, points = ? WHERE id = ?");
        } else {
            $provider->prepareStatement("INSERT INTO factions (fName, leader_xuid, deathsUntilRaidable, regenCooldown, balance, points, id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        }

        $provider->set(
            $this->factionData->getName(),
            $this->factionData->getLeaderXuid(),
            $this->factionData->getDeathsUntilRaidable(),
            $this->factionData->getRegenCooldown(),
            $this->factionData->getBalance(),
            $this->factionData->getPoints(),
            $this->factionData->getId()
        );
        $provider->executeStatement()->close();
    }

    /**
     * This function is executed on the Main Thread because need use some function of pmmp
     */
    public function onComplete(): void {
        // TODO: Implement onComplete() method.
    }
}