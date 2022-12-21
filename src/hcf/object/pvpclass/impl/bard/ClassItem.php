<?php

declare(strict_types=1);

namespace hcf\object\pvpclass\impl\bard;

use pocketmine\entity\effect\EffectInstance;
use pocketmine\item\Item;

final class ClassItem {

    /**
     * @param string         $displayName
     * @param Item           $item
     * @param int            $energy
     * @param bool           $applyOnBard
     * @param EffectInstance[] $effects
     */
    public function __construct(
        private string $displayName,
        private Item $item,
        private int $energy,
        private bool $applyOnBard,
        private array $effects
    ) {}

    /**
     * @return string
     */
    public function getDisplayName(): string {
        return $this->displayName;
    }

    /**
     * @return Item
     */
    public function getItem(): Item {
        return $this->item;
    }

    /**
     * @return int
     */
    public function getEnergy(): int {
        return $this->energy;
    }

    /**
     * @return bool
     */
    public function isApplyOnBard(): bool {
        return $this->applyOnBard;
    }

    /**
     * @return EffectInstance[]
     */
    public function getEffects(): array {
        return $this->effects;
    }
}