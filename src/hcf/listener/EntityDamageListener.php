<?php

declare(strict_types=1);

namespace hcf\listener;

use hcf\factory\ProfileFactory;
use hcf\object\profile\ProfileTimer;
use hcf\utils\HCFUtils;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function sprintf;

final class EntityDamageListener implements Listener {

    /**
     * @param EntityDamageEvent $ev
     *
     * @priority NORMAL
     */
    public function onEntityDamageEvent(EntityDamageEvent $ev): void {
        $entity = $ev->getEntity();
        if (!$entity instanceof Player) return;
        if (($profile = ProfileFactory::getInstance()->getIfLoaded($entity->getXuid())) === null) {
            $ev->cancel();

            return;
        }

        if (HCFUtils::isSotwRunning()) {
            $ev->cancel();

            return;
        }

        if (!$profile->getClaimRegion()->isDeathBan()) $ev->cancel();
        if (($profileTimer = $profile->getProfileTimer(ProfileTimer::PVP_TAG)) !== null && $profileTimer->isRunning()) $ev->cancel();

        if (!$ev instanceof EntityDamageByEntityEvent) return;

        $attacker = $ev->getDamager();
        if (!$attacker instanceof Player || ($attackerProfile = ProfileFactory::getInstance()->getIfLoaded($attacker->getXuid())) === null) {
            $ev->cancel();

            return;
        }

        if ($attackerProfile->getFactionId() !== null && $attackerProfile->getFactionId() === $profile->getFactionId()) {
            $attacker->sendMessage(TextFormat::DARK_GREEN . $entity->getName() . TextFormat::YELLOW . ' is in your faction.');

            $ev->cancel();

            return;
        }

        if (!$profile->getClaimRegion()->isDeathBan()) {
            $attacker->sendMessage(TextFormat::RED . 'You cannot attack players that are in safe-zones');

            return;
        }

        if (!$attackerProfile->getClaimRegion()->isDeathBan()) {
            $attacker->sendMessage(TextFormat::RED . 'You cannot attack players whilst in safe-zones.');

            return;
        }

        if (($profileTimer = $profile->getProfileTimer(ProfileTimer::PVP_TAG)) !== null && ($remainingTime = $profileTimer->getRemainingTime()) > 0) {
            $attacker->sendMessage(TextFormat::RED . $profile->getName() . ' has their ' . $profileTimer->getNameColoured() . TextFormat::RED . ' timer for another ' . TextFormat::BOLD . HCFUtils::dateString($remainingTime));

            $ev->cancel();

            return;
        }

        if (($profileTimer = $attackerProfile->getProfileTimer(ProfileTimer::PVP_TAG)) !== null && ($remainingTime = $profileTimer->getRemainingTime()) > 0) {
            $attacker->sendMessage(TextFormat::colorize(sprintf('&cYou cannot attack players whilst your %s&c timer is active [&l%s&r&c remaining]. Use \'&7/pvp enable&c\' to allow pvp.', $profileTimer->getNameColoured(), HCFUtils::dateString($remainingTime))));

            $ev->cancel();

            return;
        }

        $profile->toggleProfileTimer(ProfileTimer::COMBAT_TAG);
        $attackerProfile->toggleProfileTimer(ProfileTimer::COMBAT_TAG);
    }
}