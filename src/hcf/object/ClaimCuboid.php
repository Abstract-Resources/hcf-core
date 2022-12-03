<?php

declare(strict_types=1);

namespace hcf\object;

use hcf\utils\HCFUtils;
use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
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

    public function recalculate(): void {
        $this->firstCorner = new Position(
            min($this->firstCorner->getFloorX(), $this->secondCorner->getFloorX()),
            min($this->firstCorner->getFloorY(), $this->secondCorner->getFloorY()),
            min($this->firstCorner->getFloorZ(), $this->secondCorner->getFloorZ()),
            $this->firstCorner->getWorld()
        );

        $this->secondCorner = new Position(
            max($this->firstCorner->getFloorX(), $this->secondCorner->getFloorX()),
            max($this->firstCorner->getFloorY(), $this->secondCorner->getFloorY()),
            max($this->firstCorner->getFloorZ(), $this->secondCorner->getFloorZ()),
            $this->firstCorner->getWorld()
        );
    }

    /**
     * @param Position $position
     *
     * @return bool
     */
    public function isInside(Position $position): bool {
        if ($position->getWorld() !== $this->firstCorner->getWorld()) return false;

        return $position->getFloorX() >= $this->firstCorner->getFloorX() && $position->getFloorX() <= $this->secondCorner->getFloorX() &&
            $position->getFloorZ() >= $this->firstCorner->getFloorZ() && $position->getFloorZ() <= $this->secondCorner->getFloorZ();
    }

    /**
     * @param Player  $player
     * @param Block   $block
     * @param Vector3 $vector
     */
    public static function growTower(Player $player, Block $block, Vector3 $vector): void {
        $glass = false;

        for($i = $vector->getFloorY() + 1; $i <= World::Y_MAX; $i++) {
            $player->getNetworkSession()->sendDataPacket(UpdateBlockPacket::create(
                BlockPosition::fromVector3(new Vector3($vector->getX(), $i, $vector->getZ())),
                RuntimeBlockMapping::getInstance()->toRuntimeId(($glass && !$block instanceof Air ? VanillaBlocks::GLASS() : $block)->getFullId()),
                UpdateBlockPacket::FLAG_NETWORK,
                UpdateBlockPacket::DATA_LAYER_NORMAL
            ));

            $glass = !$glass;
        }
    }

    /**
     * @param int $number
     *
     * @return ClaimCuboid
     */
    public static function fromNumber(int $number): ClaimCuboid {
        $cuboid = new self (
            new Position($number, World::Y_MIN, $number, HCFUtils::getDefaultWorld()),
            new Position(-$number, World::Y_MAX, -$number, HCFUtils::getDefaultWorld())
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
            new Position($storage['firstX'], $storage['firstY'], $storage['firstZ'], HCFUtils::getDefaultWorld()),
            new Position($storage['secondX'], $storage['secondY'], $storage['secondZ'], HCFUtils::getDefaultWorld())
        );
        $cuboid->recalculate();

        return $cuboid;
    }
}