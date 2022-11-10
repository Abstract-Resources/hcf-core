<?php

declare(strict_types=1);

namespace hcf\object\profile\query;

use hcf\factory\ProfileFactory;
use hcf\object\profile\Profile;
use hcf\object\profile\ProfileData;
use hcf\thread\query\MySQL;
use hcf\thread\query\Query;
use hcf\utils\HCFUtils;
use mysqli_result;
use pocketmine\plugin\PluginException;
use function is_array;
use function is_int;

final class LoadProfileQuery extends Query {

    /** @var Profile|null */
    private ?Profile $profile = null;

    /**
     * @param string $xuid
     * @param string $name
     */
    public function __construct(
        private string $xuid,
        private string $name
    ) {}

    /**
     * @param MySQL $provider
     *
     * This function is executed on other Thread to prevent lag spike on Main thread
     */
    public function run(MySQL $provider): void {
        $provider->prepareStatement('SELECT * FROM profiles WHERE xuid = ?');
        $provider->set($this->xuid);

        $stmt = $provider->executeStatement();
        if (!($result = $stmt->get_result()) instanceof mysqli_result) {
            throw new PluginException('An error occurred while tried fetch profile');
        }

        if (is_array($fetch = $result->fetch_array(MYSQLI_ASSOC))) {
            $this->profile = new Profile(
                $this->xuid,
                $this->name,
                $fetch['first_seen'],
                $fetch['last_seen'],
                $fetch['faction_id'] ?? -1,
                $fetch['faction_role'] ?? ProfileData::MEMBER_ROLE,
                is_int($kills = $fetch['kills'] ?? 0) ? $kills : 0,
                is_int($deaths = $fetch['deaths'] ?? 0) ? $deaths : 0
            );
        }

        $result->close();
        $stmt->close();
    }

    /**
     * This function is executed on the Main Thread because need use some function of pmmp
     */
    public function onComplete(): void {
        if ($this->profile === null) {
            $this->profile = new Profile($this->xuid, $this->name, $now = HCFUtils::dateNow(), $now);
            $this->profile->forceSave(false);
        }

        ProfileFactory::getInstance()->registerNewProfile($this->profile);
    }
}