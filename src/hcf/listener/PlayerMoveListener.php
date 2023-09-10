<?php

declare(strict_types=1);

namespace hcf\listener;

use hcf\factory\FactionFactory;
use hcf\factory\ProfileFactory;
use hcf\HCFLanguage;
use hcf\object\ClaimCuboid;
use hcf\object\profile\Profile;
use hcf\object\profile\ProfileTimer;
use hcf\utils\ServerUtils;
use pocketmine\block\Air;
use pocketmine\block\Planks;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\Water;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use function abs;
use function count;
use function in_array;

final class PlayerMoveListener implements Listener {

    private const WALL_BORDER_HEIGHT_BELOW_DIFF = 3;
    private const WALL_BORDER_HEIGHT_ABOVE_DIFF = 4;
    private const WALL_BORDER_HORIZONTAL_DISTANCE = 7;

    /**
     * @param PlayerMoveEvent $ev
     *
     * @priority NORMAL
     */
    public function onPlayerMoveEvent(PlayerMoveEvent $ev): void {
        $player = $ev->getPlayer();

        $from = $ev->getFrom()->asPosition();
        $to = $ev->getTo()->asPosition();

        if ($from->equals($to)) return;
        if (($profile = ProfileFactory::getInstance()->getIfLoaded($player->getXuid())) === null) return;

        $this->handleCombatWall($profile, $player, $from, $to);

        if (($profileTimer = $profile->getProfileTimer(ProfileTimer::HOME_TAG)) !== null && $profileTimer->isRunning()) {
            $profileTimer->cancel();
        }

        $targetClaim = FactionFactory::getInstance()->getRegionAt($to);

        if (FactionFactory::getInstance()->getFactionAt($to) !== null && ($profileTimer = $profile->getProfileTimer(ProfileTimer::PVP_TAG)) !== null && $profileTimer->isRunning()) {
            $ev->setTo($ev->getFrom());
        }

        $currentClaim = $profile->getClaimRegion();

        if ($currentClaim->getName() === $targetClaim->getName()) return;

        if (($profileTimer = $profile->getProfileTimer(ProfileTimer::PVP_TAG)) !== null && $profileTimer->isRunning()) {
            if ($targetClaim->isDeathBan()) {
                $profileTimer->continue();
            } else {
                $profileTimer->pause();
            }
        }

        $profile->setClaimRegion($targetClaim);
        $profile->updateScoreboard();

        $player->sendMessage(HCFLanguage::PLAYER_CLAIM_LEAVE()->build($currentClaim->getCustomName(), $currentClaim->isDeathBan() ? TextFormat::RED . 'Deathban' : TextFormat::GREEN . 'Non-Deathban'));
        $player->sendMessage(HCFLanguage::PLAYER_CLAIM_ENTER()->build($targetClaim->getCustomName(), $targetClaim->isDeathBan() ? TextFormat::RED . 'Deathban' : TextFormat::GREEN . 'Non-Deathban'));
    }

    /**
     * @param Profile  $profile
     * @param Player   $player
     * @param Position $from
     * @param Position $to
     */
    private function handleCombatWall(Profile $profile, Player $player, Position $from, Position $to): void {
        if (abs($from->getX() - $to->getX()) === 0.5 && abs($from->getZ() - $to->getZ()) === 0.5) return;
        if (($from->getX() - 0.5 === $to->getX() && $from->getZ() - 0.5 === $to->getZ()) || ($from->getX() + 0.5 === $to->getX() && $from->getZ() + 0.5 === $to->getZ())) return;

        if (($profileTimer = $profile->getProfileTimer(ProfileTimer::COMBAT_TAG)) === null || !$profileTimer->isRunning()) return;

        // Values used to calculate the new visual cuboid height.
        $minHeight = $to->getFloorY() - self::WALL_BORDER_HEIGHT_BELOW_DIFF;
        $maxHeight = $to->getFloorY() + self::WALL_BORDER_HEIGHT_ABOVE_DIFF;
        $minX = $to->getFloorX() - self::WALL_BORDER_HORIZONTAL_DISTANCE;
        $maxX = $to->getFloorX() + self::WALL_BORDER_HORIZONTAL_DISTANCE;
        $minZ = $to->getFloorZ() - self::WALL_BORDER_HORIZONTAL_DISTANCE;
        $maxZ = $to->getFloorZ() + self::WALL_BORDER_HORIZONTAL_DISTANCE;

        $claims = FactionFactory::getInstance()->getClaimsAt(new Vector3($minX, $minHeight, $minZ), new Vector3($maxX, $maxHeight, $maxZ));

        if (count($claims) === 0) {
            return;
        }

        $toUpdate = [];

        foreach ($claims as $claimRegion) {
            if ($claimRegion->getName() !== ServerUtils::REGION_SPAWN) continue;

            $firstCorner = $claimRegion->getCuboid()->getFirstCorner();
            $secondCorner = $claimRegion->getCuboid()->getSecondCorner();

            $closerX = $this->closestNumber($to->getFloorX(), $firstCorner->getFloorX(), $secondCorner->getFloorX());
            $closerZ = $this->closestNumber($to->getFloorZ(), $firstCorner->getFloorZ(), $secondCorner->getFloorZ());

            $updateX = abs($to->getFloorX() - $closerX) < 10;
            $updateZ = abs($to->getFloorZ() - $closerZ) < 10;

            if (!$updateX && !$updateZ) continue;

            if ($updateX) {
                for ($y = -5; $y < 5; ++$y) {
                    for ($x = -5; $x < 5; ++$x) {
                        if (!$this->between($firstCorner->getFloorZ(), $secondCorner->getFloorZ(), $to->getFloorZ() + $x)) continue;

                        if (in_array($targetVector = new Vector3($closerX, $to->getFloorY() + $y, $to->getFloorZ() + $x), $toUpdate, true)) continue;

                        $block = $to->getWorld()->getBlock($targetVector);

                        if (!$block instanceof Air && !$block instanceof Water && !$block instanceof Planks) continue;

                        $profile->glassCached[] = $targetVector = Position::fromObject($targetVector, $to->getWorld());
                        $toUpdate[] = $targetVector;
                    }
                }
            }

            if ($updateZ) {
                for ($y = -5; $y < 5; ++$y) {
                    for ($x = -5; $x < 5; ++$x) {
                        if (!$this->between($firstCorner->getFloorX(), $secondCorner->getFloorX(), $to->getFloorX() + $x)) {
                            continue;
                        }

                        if (in_array($targetVector = new Vector3($to->getFloorX() + $x, $to->getFloorY() + $y, $closerZ), $toUpdate, true)) {
                            continue;
                        }

                        $block = $to->getWorld()->getBlock($targetVector);

                        if (!$block instanceof Air && !$block instanceof Water && !$block instanceof Planks) {
                            continue;
                        }

                        $profile->glassCached[] = $targetVector = Position::fromObject($targetVector, $to->getWorld());
                        $toUpdate[] = $targetVector;
                    }
                }
            }
        }

        foreach ($toUpdate as $position) {
            ClaimCuboid::sendBlockChange($player, VanillaBlocks::STAINED_HARDENED_GLASS()->setColor(DyeColor::RED()), $position);
        }
    }

    /**
     * @param int $xone
     * @param int $xother
     * @param int $mid
     *
     * @return bool
     */
    private function between(int $xone, int $xother, int $mid): bool {
        return abs($xone - $xother) === abs($mid - $xone) + abs($mid - $xother);
    }

    private function closestNumber(int $from, int...$numbers): int {
        $distance = abs($numbers[0] - $from);
        $idx = 0;

        foreach ($numbers as $c => $cdistance) {
            $cdistance = abs($cdistance - $from);

            if ($cdistance >= $distance) {
                continue;
            }

            $idx = $c;

            $distance = $cdistance;
        }

        return $numbers[$idx];
    }
}