<?php

declare(strict_types=1);

namespace hcf\object\faction;

final class Faction {

    /**
     * @param int    $rowId
     * @param string $name
     */
    public function __construct(
        private int $rowId,
        private string $name
    ) {}

    /**
     * @return int
     */
    public function getRowId(): int {
        return $this->rowId;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void {
        $this->name = $name;
    }
}