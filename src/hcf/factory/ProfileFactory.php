<?php

declare(strict_types=1);

namespace hcf\factory;

use hcf\object\profile\Profile;
use hcf\object\profile\ProfileTimer;
use hcf\utils\HCFUtils;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use function array_filter;
use function array_map;

final class ProfileFactory {
    use SingletonTrait;

    /** @var array<string, Profile> */
    private array $profiles = [];

    /**
     * @param Profile $profile
     * @param bool    $joinedBefore
     */
    public function registerNewProfile(Profile $profile, bool $joinedBefore): void {
        if (isset($this->profiles[$profile->getXuid()])) return;

        $profile->init();

        $this->profiles[$profile->getXuid()] = $profile;

        if ($joinedBefore) return;

        $profile->toggleProfileTimer(ProfileTimer::PVP_TAG);
        $profile->forceSave(false);
    }

    /**
     * @param string $xuid
     *
     * @return Profile|null
     */
    public function getIfLoaded(string $xuid): ?Profile {
        return $this->profiles[$xuid] ?? null;
    }

    /**
     * @param string $name
     *
     * @return Profile|null
     */
    public function getPlayerProfile(string $name): ?Profile {
        if (($target = Server::getInstance()->getPlayerByPrefix($name)) === null) return null;

        return $this->getIfLoaded($target->getXuid());
    }

    /**
     * @param string $xuid
     */
    public function unregisterProfile(string $xuid): void {
        if (($profile = $this->getIfLoaded($xuid)) === null) return;

        $profile->hideScoreboard();

        HCFUtils::storeProfileTimers($profile->getXuid(), array_map(fn(ProfileTimer $timer) => [
        	'name' => $timer->getName(),
        	'remaining' => $timer->getRemainingTime()
        ], array_filter($profile->getStoredTimers(), fn(ProfileTimer $timer) => $timer->getName() !== ProfileTimer::COMBAT_TAG)));

        unset($this->profiles[$xuid]);
    }
}