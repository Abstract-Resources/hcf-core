<?php

declare(strict_types=1);

namespace hcf\factory;

use Exception;
use hcf\HCFCore;
use hcf\object\ClaimCuboid;
use hcf\utils\HCFUtils;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use function is_int;
use function is_string;
use function mb_strtoupper;

final class KothFactory {
    use SingletonTrait;

    /** @var array<string, ClaimCuboid> */
    private array $kothsCuboid = [];
    /** @var array<string, int> */
    private array $kothsTime = [];

    /** @var string|null */
    private ?string $currentKoth = null;
    /** @var int */
    private int $capturingTime = 0;
    /** @var string|null */
    private ?string $targetName = null;

    public function init(): void {
        foreach ((new Config(HCFCore::getInstance()->getDataFolder() . 'koths.yml'))->getAll() as $kothName => $kothTime) {
            if (!is_string($kothName) || !is_int($kothTime)) continue;

            if (($claimRegion = FactionFactory::getInstance()->getKothClaim($kothName)) === null) continue;

            $this->registerKoth($kothName, $claimRegion->getCuboid(), $kothTime);
        }

        HCFCore::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(fn() => $this->tick()), 20);
    }

    /**
     * @param string      $kothName
     * @param ClaimCuboid $cuboid
     * @param int         $kothTime
     * @param bool        $overwirte
     */
    public function registerKoth(string $kothName, ClaimCuboid $cuboid, int $kothTime, bool $overwirte = false): void {
        $this->kothsCuboid[$kothName] = $cuboid;
        $this->kothsTime[$kothName] = $kothTime;

        if (!$overwirte) return;

        $config = new Config(HCFCore::getInstance()->getDataFolder() . 'koths.yml');
        $config->set($kothName, $kothTime);
        try {
            $config->save();
        } catch (Exception $e) {
            Server::getInstance()->getLogger()->logException($e);
        }
    }

    /**
     * @param string|null $kothName
     */
    public function setCurrentKoth(?string $kothName): void {
        $this->currentKoth = $kothName;

        $this->targetName = null;

        if ($kothName === null) return;

        $this->capturingTime = $this->kothsTime[$kothName] ?? 600;
    }

    /**
     * @return string|null
     */
    public function getKothName(): ?string {
        if ($this->currentKoth === null) return null;

        return HCFUtils::replacePlaceholders('KOTH_' . mb_strtoupper($this->currentKoth) . '_NAME', [$this->currentKoth]);
    }

    /**
     * @return int
     */
    public function getCapturingTime(): int {
        return $this->capturingTime;
    }

    public function tick(): void {
        if ($this->currentKoth === null) return;
        if (($cuboid = $this->kothsCuboid[$this->currentKoth] ?? null) === null) return;

        $target = $this->targetName !== null ? Server::getInstance()->getPlayerExact($this->targetName) : null;

        if ($target === null || !$target->isConnected()) {
            foreach ($cuboid->getFirstCorner()->getWorld()->getNearbyEntities($cuboid->getAxisAligned()) as $targetEntity) {
                if (!$targetEntity instanceof Player) continue;
                if (!$cuboid->isInside($targetEntity->getPosition())) continue;

                if (($profile = ProfileFactory::getInstance()->getIfLoaded($targetEntity->getXuid())) === null || $profile->getFactionId() === null) continue;

                $this->targetName = $targetEntity->getName();

                $targetEntity->sendMessage(HCFUtils::replacePlaceholders('PLAYER_KOTH_CONTROLLING', [$this->currentKoth]));

                Server::getInstance()->broadcastMessage(HCFUtils::replacePlaceholders('KOTH_SOMEONE_CONTROLLING', [$targetEntity->getName(), $this->currentKoth]));

                break;
            }

            return;
        }

        if (
            ($profile = ProfileFactory::getInstance()->getIfLoaded($target->getXuid())) === null ||
            $profile->getFactionId() === null ||
            ($faction = FactionFactory::getInstance()->getFaction($profile->getFactionId())) === null ||
            !$cuboid->isInside($target->getPosition())
        ) {
            $target->sendMessage(HCFUtils::replacePlaceholders('PLAYER_KOTH_CONTROLLING_LOST', [$this->currentKoth]));

            Server::getInstance()->broadcastMessage(HCFUtils::replacePlaceholders('KOTH_CONTROLLING_LOST', [$target->getName(), $this->currentKoth]));

            $this->targetName = null;

            $this->capturingTime = $this->kothsTime[$this->currentKoth] ?? 600;

            return;
        }

        if ($this->capturingTime-- > 1) return;

        Server::getInstance()->broadcastMessage(HCFUtils::replacePlaceholders('KOTH_CAPTURING_END', [$target->getName(), $faction->getName(), $this->currentKoth]));

        $this->setCurrentKoth(null);
    }
}