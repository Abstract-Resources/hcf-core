<?php

declare(strict_types=1);

namespace hcf\listener\claim;

use hcf\command\faction\arguments\leader\ClaimArgument;
use hcf\factory\FactionFactory;
use hcf\HCFCore;
use hcf\object\ClaimCuboid;
use hcf\object\ClaimRegion;
use hcf\utils\ServerUtils;
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
     * @throws \JsonException
     */
    public function onPlayerChatEvent(PlayerChatEvent $ev): void {
        $player = $ev->getPlayer();

        if (($item = self::getClaimingWand($player->getInventory())) === null) return;

        $claimType = $item->getNamedTag()->getByte('claim_type');

        if (($cuboid = ClaimRegion::getIfClaiming($player)) === null) return;
        if (($faction = FactionFactory::getInstance()->getPlayerFaction($player)) === null && $claimType === ClaimArgument::FACTION_CLAIMING) return;

        if (!in_array(strtolower($ev->getMessage()), ['accept', 'cancel'], true)) return;

        $ev->cancel();

        $zero = ServerUtils::posZero($player->getWorld());
        if (!$zero->equals($firstCorner = $cuboid->getFirstCorner())) {
            ClaimCuboid::growTower($player, VanillaBlocks::AIR(), $firstCorner);
        }

        if (!$zero->equals($secondCorner = $cuboid->getSecondCorner())) {
            ClaimCuboid::growTower($player, VanillaBlocks::AIR(), $secondCorner);
        }

        $player->getInventory()->remove($item);

        ClaimRegion::flush($player->getXuid());

        if (strtolower($ev->getMessage()) === 'cancel') {
            return;
        }

        if (!$cuboid->hasBothPositionsSet($player->getWorld())) return;

        if (($claimTag = $item->getNamedTag()->getTag('claim_name')) !== null) {
            FactionFactory::getInstance()->registerAdminClaim(new ClaimRegion(
                is_string($claimName = $claimTag->getValue()) ? $claimName : ServerUtils::REGION_SPAWN,
                $cuboid,
                []
            ), true);

            $player->sendMessage(TextFormat::GREEN . 'Admin claim was successfully saved!');

            return;
        }

        if ($faction === null) {
            $player->sendMessage(TextFormat::RED . 'An error occurred');

            return;
        }

        $price = ClaimPlayerInteractListener::calculateClaimPrice($cuboid);

        if ($price > $faction->getBalance()) {
            $player->sendMessage(ServerUtils::replacePlaceholders('FACTION_HAVE_NOT_BALANCE_ENOUGH'));

            return;
        }

        $cuboid->recalculate();

        for ($x = $firstCorner->getFloorX(); $x <= $secondCorner->getFloorX(); $x++) {
            for ($z = $firstCorner->getFloorZ(); $z <= $secondCorner->getFloorZ(); $z++) {
                $claimRegion = FactionFactory::getInstance()->getRegionAt(new Position($x, World::Y_MAX, $z, $player->getWorld()));

                if ($claimRegion->getName() === ServerUtils::REGION_WILDERNESS) continue;

                $player->sendMessage(ServerUtils::replacePlaceholders('INVALID_CLAIM_POSITION'));

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