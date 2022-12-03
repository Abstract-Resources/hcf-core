<?php

declare(strict_types=1);

namespace hcf\listener;

use hcf\factory\FactionFactory;
use hcf\factory\ProfileFactory;
use hcf\object\profile\timer\PlayerTimer;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;

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

        if ($ev instanceof EntityDamageByEntityEvent) {
            if (FactionFactory::getInstance()->isInsideSpawn($entity->getPosition())) {
                $ev->cancel();

                return;
            }

            $target = $ev->getDamager();
            if (!$target instanceof Player || ($targetProfile = ProfileFactory::getInstance()->getIfLoaded($target->getXuid())) === null) return;

            $profile->updateTimer(PlayerTimer::COMBAT_TAG);
            $targetProfile->updateTimer(PlayerTimer::COMBAT_TAG);
        }
    }
}