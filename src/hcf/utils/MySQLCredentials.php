<?php

declare(strict_types=1);

namespace hcf\utils;

use pocketmine\plugin\PluginException;

final class MySQLCredentials {

    /**
     * @param string $address
     * @param int    $port
     * @param string $username
     * @param string $password
     * @param string $dbname
     */
    public function __construct(
        private string $address,
        private int $port,
        private string $username,
        private string $password,
        private string $dbname
    ) {}

    /**
     * @return string
     */
    public function getAddress(): string {
        return $this->address;
    }

    /**
     * @return int
     */
    public function getPort(): int {
        return $this->port;
    }

    /**
     * @return string
     */
    public function getUsername(): string {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getPassword(): string {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getDbname(): string {
        return $this->dbname;
    }

    /**
     * @param string $host
     *
     * @return array
     */
    public static function parseHost(string $host): array {
        $split = explode(':', $host, 2);

        if (count($split) === 0) {
            throw new PluginException('An error occurred while tried parse the host');
        }

        if (count($split) !== 2) {
            return [$split[0], 3306];
        }

        return $split;
    }
}