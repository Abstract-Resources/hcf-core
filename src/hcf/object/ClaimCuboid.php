<?php

declare(strict_types=1);

namespace hcf\object;

use hcf\utils\ServerUtils;
use pocketmine\block\Block;
use pocketmine\block\DiamondOre;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\world\World;
use function max;
use function min;

final class ClaimCuboid {

    /** @var AxisAlignedBB */
    private AxisAlignedBB $bb;

    /**
     * @param Position $firstCorner
     * @param Position $secondCorner
     */
    public function __construct(
        private Position $firstCorner,
        private Position $secondCorner
    ) {}

    /**
     * @return Position
     */
    public function getFirstCorner(): Position {
        return $this->firstCorner;
    }

    /**
     * @param Position $firstCorner
     */
    public function setFirstCorner(Position $firstCorner): void {
        $this->firstCorner = $firstCorner;
    }

    /**
     * @return Position
     */
    public function getSecondCorner(): Position {
        return $this->secondCorner;
    }

    /**
     * @param Position $secondCorner
     */
    public function setSecondCorner(Position $secondCorner): void {
        $this->secondCorner = $secondCorner;
    }

    /**
     * @return int
     */
    public function getArea(): int {
        return (($this->secondCorner->getFloorX() - $this->firstCorner->getFloorX() + 1) * ($this->secondCorner->getFloorZ() - $this->firstCorner->getFloorZ() + 1));
    }

    /**
     * @param World $world
     *
     * @return bool
     */
    public function hasBothPositionsSet(World $world): bool {
        return !($zero = ServerUtils::posZero($world))->equals($this->firstCorner) && !$zero->equals($this->secondCorner);
    }

    public function recalculate(): void {
        $firstCorner = $this->firstCorner;
        $secondCorner = $this->secondCorner;

        $this->bb = new AxisAlignedBB(
            min($firstCorner->getFloorX(), $secondCorner->getFloorX()),
            min($firstCorner->getFloorY(), $secondCorner->getFloorY()),
            min($firstCorner->getFloorZ(), $secondCorner->getFloorZ()),
            max($firstCorner->getFloorX(), $secondCorner->getFloorX()),
            max($firstCorner->getFloorY(), $secondCorner->getFloorY()),
            max($firstCorner->getFloorZ(), $secondCorner->getFloorZ())
        );
    }

    /**
     * @return AxisAlignedBB
     */
    public function getAxisAligned(): AxisAlignedBB {
        return $this->bb;
    }
    /**
     * @param Position $vector
     *
     * @return bool
     */
    public function isInside(Position $vector): bool {
        if ($vector->world !== $this->firstCorner->world) return false;

        return $this->bb->isVectorInXZ($vector);
    }

    public static function growTower(Player $player, Block $block, Vector3 $vector): void {
        $glass = false;

        for($i = $vector->getFloorY() + 1; $i <= World::Y_MAX; $i++) {
            self::sendBlockChange($player, $glass && $block instanceof DiamondOre ? VanillaBlocks::GLASS() : $block, $vector->add(0, 1, 0));

            $glass = !$glass;
        }
    }

    /**
     * @param Player  $player
     * @param Block   $block
     * @param Vector3 $vector
     */
    public static function sendBlockChange(Player $player, Block $block, Vector3 $vector): void {
        $player->getNetworkSession()->sendDataPacket(UpdateBlockPacket::create(
            BlockPosition::fromVector3($vector),
            RuntimeBlockMapping::getInstance()->toRuntimeId($block->getFullId()),
            UpdateBlockPacket::FLAG_NETWORK,
            UpdateBlockPacket::DATA_LAYER_NORMAL
        ));
    }

    /**
     * @param int $number
     *
     * @return ClaimCuboid
     */
    public static function fromNumber(int $number): ClaimCuboid {
        $cuboid = new self (
            new Position(-$number, World::Y_MIN, -$number, ServerUtils::getDefaultWorld()),
            new Position($number, World::Y_MAX, $number, ServerUtils::getDefaultWorld())
        );
        $cuboid->recalculate();

        return $cuboid;
    }

    /**
     * @param array $storage
     *
     * @return ClaimCuboid
     */
    public static function fromStorage(array $storage): ClaimCuboid {
        $cuboid = new self(
            new Position($storage['firstX'], $storage['firstY'], $storage['firstZ'], ServerUtils::getDefaultWorld()),
            new Position($storage['secondX'], $storage['secondY'], $storage['secondZ'], ServerUtils::getDefaultWorld())
        );
        $cuboid->recalculate();

        return $cuboid;
    }
}