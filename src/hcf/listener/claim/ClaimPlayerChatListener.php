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
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
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

        if (($item = self::getClaimingWand($player->getInventory())) === null) return;

        $claimType = $item->getNamedTag()->getByte('claim_type');

        if (($cuboid = ClaimRegion::getIfClaiming($player)) === null) return;
        if (($faction = FactionFactory::getInstance()->getPlayerFaction($player)) === null && $claimType === ClaimArgument::FACTION_CLAIMING) return;

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

        $player->getInventory()->remove($item);

        ClaimRegion::flush($player->getXuid());

        if (strtolower($ev->getMessage()) === 'cancel') {
            return;
        }

        $zero = HCFUtils::posZero($player->getWorld());
        if ($zero->equals($firstCorner) || $zero->equals($secondCorner)) return;

        if ($faction === null) {
            FactionFactory::getInstance()->registerAdminClaim(new ClaimRegion(
                $item->getNamedTag()->getString('claim_name'),
                $cuboid,
                []
            ), true);

            $player->sendMessage(TextFormat::GREEN . 'Admin claim was successfully saved!');

            return;
        }

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
            new ClaimRegion($faction->getName(), $cuboid),
            $faction->getId()
        );
    }

    /**
     * @param Inventory $inventory
     *
     * @return Item|null
     */
    public static function getClaimingWand(Inventory $inventory): ?Item {
        foreach ($inventory->getContents() as $item) {
            if ($item->getNamedTag()->getTag('claim_type') === null) continue;

            return $item;
        }

        return null;
    }
}