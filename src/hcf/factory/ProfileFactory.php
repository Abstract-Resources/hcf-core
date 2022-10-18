<?php

declare(strict_types=1);

namespace hcf\factory;

use hcf\object\profile\Profile;
use pocketmine\utils\SingletonTrait;

final class ProfileFactory {
    use SingletonTrait;

    /** @var array<string, Profile> */
    private array $profiles = [];

    /**
     * @param Profile $profile
     */
    public function registerNewProfile(Profile $profile): void {
        if (isset($this->profiles[$profile->getXuid()])) return;

        $this->profiles[$profile->getXuid()] = $profile;
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
     * @param string $xuid
     */
    public function unregisterProfile(string $xuid): void {
        unset($this->profiles[$xuid]);
    }
}