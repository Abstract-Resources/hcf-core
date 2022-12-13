<?php

declare(strict_types=1);

namespace hcf\object\pvpclass\impl;

use hcf\object\profile\Profile;
use hcf\object\pvpclass\PvpClass;
use function array_merge;

final class BardPvpClass extends PvpClass {

    /**
     * @return string
     */
    public function getScoreboardPlaceholder(): string {
        return 'bard_class_lines';
    }

    /**
     * @param Profile $profile
     *
     * @return array
     */
    public function getScoreboardLines(Profile $profile): array {
        return array_merge(parent::getScoreboardLines($profile), [
        	'bard_energy' => 0
        ]);
    }

    /**
     * @param Profile $profile
     */
    public function onItemInteract(Profile $profile): void {
        // TODO: Implement onItemInteract() method.
    }

    /**
     * @param Profile $profile
     */
    public function onHeldItem(Profile $profile): void {
        // TODO: Implement onHeldItem() method.
    }
}