<?php

declare(strict_types=1);

namespace hcf\listener;

use hcf\factory\FactionFactory;
use hcf\factory\ProfileFactory;
use hcf\HCFLanguage;
use hcf\object\profile\ProfileTimer;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\utils\TextFormat;

final class PlayerMoveListener implements Listener {

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

        if (($profileTimer = $profile->getProfileTimer(ProfileTimer::HOME_TAG)) !== null && $profileTimer->isRunning()) {
            $profileTimer->cancel();
        }

        $targetClaim = FactionFactory::getInstance()->getRegionAt($to);
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
}