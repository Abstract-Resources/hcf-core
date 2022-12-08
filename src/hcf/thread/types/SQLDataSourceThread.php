<?php

declare(strict_types=1);

namespace hcf\thread\types;

use hcf\thread\datasource\MySQL;
use hcf\thread\datasource\MySQLCredentials;
use hcf\thread\datasource\SqlException;
use ThreadedLogger;
use function intval;
use function min;
use function sleep;

final class SQLDataSourceThread implements ThreadType {

    private ?MySQL $resource = null;

    private ThreadedLogger $logger;

    /**
     * @param MySQLCredentials $credentials
     */
    public function __construct(
        private MySQLCredentials $credentials
    ) {}

    /**
     * @param ThreadedLogger $logger
     */
    public function init(ThreadedLogger $logger): void {
        $this->resource = new MySQL(
            $this->credentials->getAddress(),
            $this->credentials->getUsername(),
            $this->credentials->getPassword(),
            $this->credentials->getDbname(),
            $this->credentials->getPort()
        );

        if ($this->resource->connect_error !== null) {
            throw new SqlException('An error occurred while connecting to \'' . $this->credentials->getAddress() . '@' . $this->credentials->getUsername() . '\'');
        }

        $this->logger = $logger;
    }

    /**
     * @return MySQL
     */
    public function getResource(): MySQL {
        if ($this->resource === null) {
            throw new SqlException('Attempt to get Resource instance but it never was initialized');
        }

        if ($this->resource->ping()) {
            return $this->resource;
        }

        $success = false;

        $attempts = 0;

        while (!$success) {
            $seconds = min(2 ** $attempts, PHP_INT_MAX);

            $this->logger->warning('MySQL Connection failed! Trying reconnecting in ' . $seconds . ' seconds.');

            sleep(intval($seconds));

            $this->resource->connect(
                $this->credentials->getAddress(),
                $this->credentials->getUsername(),
                $this->credentials->getPassword(),
                $this->credentials->getDbname(),
                $this->credentials->getPort()
            );

            if ($this->resource->connect_error !== null) {
                $attempts++;

                $this->logger->error('An error occurred while trying reconnect to \'' . $this->credentials->getAddress() . '@' . $this->credentials->getUsername() . '\': ' . $this->resource->connect_error);

                continue;
            }

            $success = true;
        }

        $this->logger->info('Successfully database connection restored!');

        return $this->resource;
    }

    /**
     * @return int
     */
    public function id(): int {
        return 0;
    }
}