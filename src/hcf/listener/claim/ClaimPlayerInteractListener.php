<?php

declare(strict_types=1);

namespace hcf\listener\claim;

use hcf\command\faction\arguments\leader\ClaimArgument;
use hcf\factory\FactionFactory;
use hcf\HCFCore;
use hcf\HCFLanguage;
use hcf\object\ClaimCuboid;
use hcf\object\ClaimRegion;
use hcf\utils\HCFUtils;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\scheduler\ClosureTask;

final class ClaimPlayerInteractListener implements Listener {

    /**
     * @param PlayerInteractEvent $ev
     *
     * @priority NORMAL
     */
    public function onPlayerInteractEvent(PlayerInteractEvent $ev): void {
        $player = $ev->getPlayer();

        if (($tag = $ev->getItem()->getNamedTag()->getTag('claim_type')) === null) return;
        if (($cuboid = ClaimRegion::getIfClaiming($player)) === null) return;

        if (FactionFactory::getInstance()->getRegionAt($vec = $ev->getBlock()->getPosition())->getName() !== HCFUtils::REGION_WILDERNESS && $tag->getValue() === ClaimArgument::FACTION_CLAIMING) return;

        if ($ev->getAction() === PlayerInteractEvent::LEFT_CLICK_BLOCK) {
            $cuboid->setFirstCorner($vec);
        } else {
            $cuboid->setSecondCorner($vec);
        }

        HCFCore::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($vec, $player): void {
            ClaimCuboid::growTower($player, VanillaBlocks::DIAMOND_ORE(), $vec);
        }), 5);

        $player->sendMessage(HCFLanguage::PLAYER_CLAIM_POSITION()->build(
            $ev->getAction() === PlayerInteractEvent::LEFT_CLICK_BLOCK ? 'first' : 'second',
            (string) $vec->x,
            (string) $vec->y,
            (string) $vec->z
        ));

        if (!$cuboid->hasBothPositionsSet($player->getWorld())) return;

        $cuboid->recalculate();

        $player->sendMessage(HCFLanguage::PLAYER_CLAIM_COST()->build(
            (string) self::calculateClaimPrice($cuboid),
            (string) ($cuboid->getFirstCorner()->getFloorX() + $cuboid->getSecondCorner()->getFloorX()),
            (string) ($cuboid->getFirstCorner()->getFloorZ() + $cuboid->getSecondCorner()->getFloorZ())
        ));
    }

    /**
     * @param ClaimCuboid $cuboid
     *
     * @return int
     */
    public static function calculateClaimPrice(ClaimCuboid $cuboid): int {
        $multiplier = 1;
        $price = 0;

        $remaining = $cuboid->getArea();
        while ($remaining > 0) {
            if (--$remaining % HCFCore::getConfigInt('factions.price-multiplier-area', 500) === 0) {
                $multiplier++;
            }

            $price += HCFCore::getConfigInt('factions.amount_per_block');
        }

        return $price;
    }
}