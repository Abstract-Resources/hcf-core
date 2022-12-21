<?php

declare(strict_types=1);

namespace hcf\object\profile;

use hcf\utils\ServerUtils;
use function strtoupper;
use function time;

final class ProfileTimer {

    public const COMBAT_TAG = 'combat_tag';
    public const PVP_TAG = 'pvp_tag';
    public const HOME_TAG = 'home_tag';

    /** @var int */
    private int $endAt = 0;

    private int $pausedRemaining = 0;

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
        return ServerUtils::replacePlaceholders('TIMER_' . strtoupper($this->name));
    }

    /**
     * @param int  $countdown
     * @param bool $paused
     */
    public function start(int $countdown = -1, bool $paused = false): void {
        if ($paused && $countdown !== -1) {
            $this->pausedRemaining = $countdown;

            return;
        }

        $this->endAt = time() + ($countdown === -1 ? $this->countdown : $countdown);
    }

    public function pause(): void {
        $this->pausedRemaining = $this->getRemainingTime();
    }

    public function continue(): void {
        if ($this->pausedRemaining === 0) return;

        $this->start($this->pausedRemaining);

        $this->pausedRemaining = 0;
    }

    public function cancel(): void {
        $this->endAt = 0;

        $this->pausedRemaining = 0;
    }

    /**
     * @return int
     */
    public function getRemainingTime(): int {
        return $this->pausedRemaining !== 0 ? $this->pausedRemaining : $this->endAt - time();
    }

    /**
     * @return bool
     */
    public function isRunning(): bool {
        return $this->getRemainingTime() > 0;
    }
}