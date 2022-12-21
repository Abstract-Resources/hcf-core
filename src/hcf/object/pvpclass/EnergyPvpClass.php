<?php

declare(strict_types=1);

namespace hcf\object\pvpclass;

interface EnergyPvpClass {

    /**
     * @return int
     */
    public function getMaxEnergy(): int;
}