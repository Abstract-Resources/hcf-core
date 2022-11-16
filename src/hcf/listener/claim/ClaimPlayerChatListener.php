<?php

declare(strict_types=1);

namespace hcf\listener\claim;

use hcf\command\faction\arguments\leader\ClaimArgument;
use hcf\factory\FactionFactory;
use hcf\HCFCore;
use hcf\object\ClaimCuboid;
use hcf\object\ClaimRegion;
use hcf\utils\HCFUtils;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\utils\Config;
use pocketmine\world\Position;
use pocketmine\world\World;
use function in_array;
use function strtolower;

final class ClaimPlayerChatListener implements Listener {

    /**
     * @param PlayerChatEvent $ev
     *
     * @priority NORMAL
     */
    public function onPlayerChatEvent(PlayerChatEvent $ev): void {
        $player = $ev->getPlayer();

        if (($faction = FactionFactory::getInstance()->getPlayerFaction($player)) === null) return;
        if (($cuboid = ClaimRegion::getIfClaiming($player)) === null) return;

        if (!in_array(strtolower($ev->getMessage()), ['accept', 'cancel'], true)) return;

        $ev->cancel();

        $firstCorner = $cuboid->getFirstCorner();
        if (!$firstCorner->equals(HCFUtils::posZero($player->getWorld()))) {
            ClaimCuboid::growTower($player, VanillaBlocks::AIR(), $firstCorner);
        }

        $secondCorner = $cuboid->getSecondCorner();
        if (!$secondCorner->equals(HCFUtils::posZero($player->getWorld()))) {
            ClaimCuboid::growTower($player, VanillaBlocks::AIR(), $secondCorner);
        }

        $player->getInventory()->remove(ClaimArgument::getClaimingWand());

        ClaimRegion::flush($player->getXuid());

        if (strtolower($ev->getMessage()) === 'cancel') {
            return;
        }

        $zero = HCFUtils::posZero($player->getWorld());
        if ($zero->equals($firstCorner) || $zero->equals($secondCorner)) return;

        $price = ClaimPlayerInteractListener::calculateClaimPrice($cuboid);

        if ($price > $faction->getBalance()) {
            $player->sendMessage(HCFUtils::replacePlaceholders('FACTION_HAVE_NOT_BALANCE_ENOUGH'));

            return;
        }

        $cuboid->recalculate();

        for ($x = $firstCorner->getFloorX(); $x <= $secondCorner->getFloorX(); $x++) {
            for ($z = $firstCorner->getFloorZ(); $z <= $secondCorner->getFloorZ(); $z++) {
                $claimRegion = FactionFactory::getInstance()->getRegionAt(new Position($x, World::Y_MAX, $z, $player->getWorld()));

                if ($claimRegion->getName() === HCFUtils::REGION_WILDERNESS) continue;

                $player->sendMessage(HCFUtils::replacePlaceholders('INVALID_CLAIM_POSITION'));

                return;
            }
        }

        $config = new Config(HCFCore::getInstance()->getDataFolder() . 'claims.json');
        $config->set($faction->getId(), [
        	'firstX' => $firstCorner->getFloorX(),
        	'firstY' => $firstCorner->getFloorY(),
        	'firstZ' => $firstCorner->getFloorZ(),
        	'secondX' => $secondCorner->getFloorX(),
        	'secondY' => $secondCorner->getFloorY(),
        	'secondZ' => $secondCorner->getFloorZ()
        ]);
        $config->save();

        FactionFactory::getInstance()->registerClaim(
            $cuboid->getFirstCorner(),
            new ClaimRegion($faction->getName(), $cuboid),
            $faction->getId()
        );
    }
}