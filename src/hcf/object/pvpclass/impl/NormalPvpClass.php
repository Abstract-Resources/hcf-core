<?php

declare(strict_types=1);

namespace hcf\object\pvpclass\impl;

use hcf\object\profile\Profile;
use hcf\object\pvpclass\PvpClass;
use pocketmine\item\Item;

final class NormalPvpClass extends PvpClass {

    /**
     * @param Profile $profile
     * @param Item    $itemHand
     */
    public function onItemInteract(Profile $profile, Item $itemHand): void {}

    /**
     * @param Profile $profile
     * @param Item    $itemHand
     */
    public function onHeldItem(Profile $profile, Item $itemHand): void {}
}