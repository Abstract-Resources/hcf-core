<?php

declare(strict_types=1);

namespace hcf\object\profile;

use hcf\utils\HCFUtils;
use function strtoupper;
use function time;

final class ProfileTimer {

    public const COMBAT_TAG = 'combat_tag';
    public const PVP_TAG = 'pvp_tag';

    /** @var int */
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

    /**
     * @return string
     */
    public function getNameColoured(): string {
        return HCFUtils::replacePlaceholders('TIMER_' . strtoupper($this->name));
    }

    /**
     * @param int $countdown
     */
    public function start(int $countdown = -1): void {
        $this->endAt = time() + ($countdown === -1 ? $this->countdown : $countdown);
    }

    /**
     * @return int
     */
    public function getRemainingTime(): int {
        return $this->endAt - time();
    }

    /**
     * @return bool
     */
    public function isRunning(): bool {
        return $this->getRemainingTime() > 0;
    }
}