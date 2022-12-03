<?php

declare(strict_types=1);

namespace hcf\object\faction;

use pocketmine\Server;

final class FactionMember {

    /**
     * @param string $xuid
     * @param string $name
     * @param int    $role
     */
    public function __construct(
        private string $xuid,
        private string $name,
        private int $role
    ) {}

    /**
     * @return string
     */
    public function getXuid(): string {
        return $this->xuid;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getRole(): int {
        return $this->role;
    }

    /**
     * @return bool
     */
    public function isOnline(): bool {
        return Server::getInstance()->getPlayerExact($this->name) !== null;
    }
}