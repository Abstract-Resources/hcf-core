<?php

declare(strict_types=1);

namespace hcf\object\faction\query;

use hcf\factory\FactionFactory;
use hcf\object\faction\Faction;
use hcf\thread\query\Query;
use hcf\utils\MySQL;
use mysqli_result;
use RuntimeException;
use Threaded;

final class LoadFactionsQuery extends Query {

    /** @var Threaded */
    private Threaded $factions;

    /**
     * @param MySQL $provider
     */
    public function run(MySQL $provider): void {
        $provider->executeStatement("CREATE TABLE IF NOT EXISTS factions (id VARCHAR(60) PRIMARY KEY, fName TEXT, deathsUntilRaidable FLOAT, regenCooldown INT, balance INT, points INT)");
        $provider->executeStatement("CREATE TABLE IF NOT EXISTS players (xuid VARCHAR(60) PRIMARY KEY, username VARCHAR(16), lives INT, balance INT, factionRowId TEXT, role INT)");

        $stmt = $provider->executeStatement("SELECT * FROM factions");
        $result = $stmt->get_result();

        if (!$result instanceof mysqli_result) {
            throw new RuntimeException('An error occurred while load factions! ' . $provider->error);
        }

        $this->factions = new Threaded();

        while ($fetch = $result->fetch_array(MYSQLI_ASSOC)) {
            $faction = new Faction(
                $fetch['id'],
                $fetch['fName'],
                $fetch['deathsUntilRaidable'],
                $fetch['regenCooldown'],
                time(),
                $fetch['balance'],
                $fetch['points']
            );

            $stmt0 = $provider->executeStatement("SELECT * FROM players WHERE factionRowId = '" . $faction->getId() . "'");
            $result0 = $stmt0->get_result();

            if (!$result0 instanceof mysqli_result) {
                throw new RuntimeException('An error occurred while load factions! ' . $provider->error);
            }

            while ($fetch0 = $result0->fetch_array(MYSQLI_ASSOC)) {
                $faction->registerMember($fetch0['xuid'],
                    $fetch0['username'],
                    $fetch0['role']
                );
            }

            $this->factions[] = $faction;
        }
    }

    public function onComplete(): void {
        while ($faction = $this->factions->shift()) {
            if (!$faction instanceof Faction) continue;

            FactionFactory::getInstance()->registerFaction($faction);
        }
    }
}