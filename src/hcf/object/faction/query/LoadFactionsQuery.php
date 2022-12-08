<?php

declare(strict_types=1);

namespace hcf\object\faction\query;

use hcf\factory\FactionFactory;
use hcf\HCFCore;
use hcf\object\ClaimCuboid;
use hcf\object\ClaimRegion;
use hcf\object\faction\Faction;
use hcf\thread\CommonThread;
use hcf\thread\LocalThreaded;
use hcf\thread\types\SQLDataSourceThread;
use hcf\thread\types\ThreadType;
use hcf\utils\HCFUtils;
use mysqli_result;
use pocketmine\entity\Location;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\world\Position;
use RuntimeException;
use Threaded;
use function is_array;
use function time;

final class LoadFactionsQuery implements LocalThreaded {

    /** @var Threaded */
    private Threaded $factions;

    /**
     * @return int
     */
    public function threadId(): int {
        return CommonThread::SQL_DATA_SOURCE;
    }

    /**
     * @param ThreadType $threadType
     *
     * This function is executed on other Thread to prevent lag spike on Main thread
     */
    public function run(ThreadType $threadType): void {
        if (!$threadType instanceof SQLDataSourceThread || $threadType->id() !== $this->threadId()) return;

        $provider = $threadType->getResource();

        $provider->executeStatement("CREATE TABLE IF NOT EXISTS factions (id VARCHAR(60) PRIMARY KEY, fName TEXT, leader_xuid VARCHAR(60), deathsUntilRaidable FLOAT, regenCooldown INT, balance INT, points INT)");
        $provider->executeStatement("CREATE TABLE IF NOT EXISTS profiles (xuid VARCHAR(60) PRIMARY KEY, username VARCHAR(16), lives INT, faction_id TEXT, faction_role INT, kills INT, deaths INT, balance INT, first_seen TEXT, last_seen TEXT)");

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
                $fetch['leader_xuid'],
                $fetch['deathsUntilRaidable'],
                $fetch['regenCooldown'],
                time(),
                $fetch['balance'],
                $fetch['points']
            );

            $stmt0 = $provider->executeStatement("SELECT * FROM profiles WHERE faction_id = '" . $faction->getId() . "'");
            $result0 = $stmt0->get_result();

            if (!$result0 instanceof mysqli_result) {
                throw new RuntimeException('An error occurred while load factions! ' . $provider->error);
            }

            while ($fetch0 = $result0->fetch_array(MYSQLI_ASSOC)) {
                $faction->registerMember($fetch0['xuid'],
                    $fetch0['username'],
                    $fetch0['faction_role']
                );
            }

            $this->factions[] = $faction;

            $result0->close();
            $stmt0->close();
        }

        $result->close();
        $stmt->close();
    }

    public function onComplete(): void {
        $config = new Config(HCFCore::getInstance()->getDataFolder() . 'claims.json');
        $config0 = new Config(HCFCore::getInstance()->getDataFolder() . 'hq.json');

        while ($faction = $this->factions->shift()) {
            if (!$faction instanceof Faction) continue;

            if (is_array($hqData = $config0->get($faction->getId())) && ($world = Server::getInstance()->getWorldManager()->getWorldByName($hqData['world'])) !== null) {
                $faction->setHqLocation(new Location($hqData['x'], $hqData['y'], $hqData['z'], $world, $hqData['yaw'], $hqData['pitch']));
            }

            FactionFactory::getInstance()->registerFaction($faction);

            if (!is_array($data = $config->get($faction->getId()))) continue;

            FactionFactory::getInstance()->registerClaim(new ClaimRegion($faction->getName(), new ClaimCuboid(
                new Position($data['firstX'], $data['firstY'], $data['firstZ'], HCFUtils::getDefaultWorld()),
                new Position($data['secondX'], $data['secondY'], $data['secondZ'], HCFUtils::getDefaultWorld())
            )), $faction->getId());
        }
    }
}