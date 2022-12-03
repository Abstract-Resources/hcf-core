<?php

declare(strict_types=1);

namespace hcf\object\profile\timer;

use function time;

final class PlayerTimer {

    public const COMBAT_TAG = 'combat_tag';
    public const PVP_TAG = 'pvp_tag';

    private int $endAt = 0;

    /**
     * @param string $name
     * @param int    $countdown
     */
    public function __construct(
        private string $name,
        private int $countdown
    ) {}

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    public function start(): void {
        $this->endAt = time() + $this->countdown;
    }

    /**
     * @return int
     */
    public function getRemainingTime(): int {
        return $this->endAt - time();
    }
}