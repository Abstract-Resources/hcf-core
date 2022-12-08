<?php

declare(strict_types=1);

namespace hcf\object\faction\query;

use hcf\object\faction\FactionData;
use hcf\thread\LocalThreaded;
use hcf\thread\types\SQLDataSourceThread;
use hcf\thread\types\ThreadType;

final class SaveFactionQuery implements LocalThreaded {

    /**
     * @param FactionData $factionData
     * @param bool        $exists
     */
    public function __construct(private FactionData $factionData, private bool $exists) {}

    /**
     * @return int
     */
    public function threadId(): int {
        return 0;
    }

    /**
     * @param ThreadType $threadType
     *
     * This function is executed on other Thread to prevent lag spike on Main thread
     */
    public function run(ThreadType $threadType): void {
        if (!$threadType instanceof SQLDataSourceThread || $threadType->id() !== $this->threadId()) return;

        $provider = $threadType->getResource();

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