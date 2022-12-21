<?php

declare(strict_types=1);

namespace hcf\object\pvpclass\impl;

use hcf\object\profile\Profile;
use hcf\object\pvpclass\impl\bard\BardItem;
use hcf\object\pvpclass\PvpClass;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\utils\TextFormat;
use function array_merge;
use function count;
use function is_array;
use function mb_strtoupper;

final class BardPvpClass extends PvpClass {

    /** @var BardItem[] */
    private array $bardItems = [];

    public function init(): void {
        $bardItems = $this->extra['bard_items'] ?? [];

        if (!is_array($bardItems) || count($bardItems) <= 0) return;

        foreach ($bardItems as $itemData) {
            if (($item = VanillaItems::getAll()[mb_strtoupper($itemData['id'])] ?? null) === null) continue;

            $this->bardItems[] = new BardItem(
                TextFormat::colorize($itemData['display_name']),
                $item,
                $itemData['energy'],
                $itemData['apply_on_bard'] ?? true,
                $itemData['other_factions'],
                PvpClass::parseEffects($itemData['effects'])
            );
        }
    }

    /**
     * @param Profile $profile
     * @param Item    $itemHand
     */
    public function onItemInteract(Profile $profile, Item $itemHand): void {
        if (($bardItem = $this->getValidItem($itemHand)) === null) return;
        // TODO: Give effects uwu

        // TODO: Need get nearest players who not are faction members
    }

    /**
     * @param Profile $profile
     * @param Item $itemHand
     */
    public function onHeldItem(Profile $profile, Item $itemHand): void {
        if (($bardItem = $this->getValidItem($itemHand)) === null) return;

    }

    /**
     * @param Item $item
     *
     * @return BardItem|null
     */
    private function getValidItem(Item $item): ?BardItem {
        foreach ($this->bardItems as $bardItem) {
            if (!$bardItem->getItem()->equals($item)) continue;

            return $bardItem;
        }

        return null;
    }

    /**
     * @return string
     */
    public function getScoreboardPlaceholder(): string {
        return 'bard_class_lines';
    }

    /**
     * @param Profile $profile
     *
     * @return array<string, string>
     */
    public function getScoreboardLines(Profile $profile): array {
        return array_merge(parent::getScoreboardLines($profile), [
        	'bard_energy' => 0
        ]);
    }
}