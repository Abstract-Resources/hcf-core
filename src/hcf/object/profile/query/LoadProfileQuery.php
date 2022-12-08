<?php

declare(strict_types=1);

namespace hcf\object\profile\query;

use hcf\factory\ProfileFactory;
use hcf\object\profile\Profile;
use hcf\object\profile\ProfileData;
use hcf\thread\CommonThread;
use hcf\thread\datasource\MySQL;
use hcf\thread\LocalThreaded;
use hcf\thread\types\SQLDataSourceThread;
use hcf\thread\types\ThreadType;
use hcf\utils\HCFUtils;
use mysqli_result;
use pocketmine\plugin\PluginException;
use function is_array;
use function is_int;

final class LoadProfileQuery implements LocalThreaded {

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
     * @param ThreadType $threadType
     *
     * This function is executed on other Thread to prevent lag spike on Main thread
     */
    public function run(ThreadType $threadType): void {
        if (!$threadType instanceof SQLDataSourceThread || $threadType->id() !== $this->threadId()) return;

        if (($profileData = self::fetch($this->xuid, $this->name, $threadType->getResource())) === null) return;

        $this->profile = Profile::fromProfileData($profileData);
    }

    public static function fetch(string $xuid, string $name, MySQL $provider): ?ProfileData {
        $provider->prepareStatement('SELECT * FROM profiles WHERE xuid = ?');
        $provider->set($xuid);

        $stmt = $provider->executeStatement();
        if (!($result = $stmt->get_result()) instanceof mysqli_result) {
            throw new PluginException('An error occurred while tried fetch profile');
        }

        $profileData = null;

        if (is_array($fetch = $result->fetch_array(MYSQLI_ASSOC))) {
            $profileData = new ProfileData(
                $xuid,
                $name,
                ($factionId = $fetch['faction_id'] ?? null) === '' ? null : $factionId,
            $fetch['faction_role'] ?? ProfileData::MEMBER_ROLE,
                is_int($kills = $fetch['kills'] ?? 0) ? $kills : 0,
                is_int($deaths = $fetch['deaths'] ?? 0) ? $deaths : 0,
                is_int($deaths = $fetch['balance'] ?? 0) ? $deaths : 0,
                $fetch['first_seen'],
                $fetch['last_seen'],
                true
            );
        }

        $result->close();
        $stmt->close();

        return $profileData;
    }

    /**
     * This function is executed on the Main Thread because need use some function of pmmp
     */
    public function onComplete(): void {
        $new = $this->profile === null;

        if ($new) {
            $this->profile = new Profile($this->xuid, $this->name, $now = HCFUtils::dateNow(), $now);
            $this->profile->forceSave(false);
        }

        if ($this->profile === null) return;

        ProfileFactory::getInstance()->registerNewProfile($this->profile, $new);
    }

    /**
     * @return int
     */
    public function threadId(): int {
        return CommonThread::SQL_DATA_SOURCE;
    }
}