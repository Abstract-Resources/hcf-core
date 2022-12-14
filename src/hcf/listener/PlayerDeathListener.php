<?php

declare(strict_types=1);

namespace hcf\listener;

use hcf\factory\FactionFactory;
use hcf\factory\ProfileFactory;
use hcf\HCFCore;
use hcf\object\profile\ProfileTimer;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function sprintf;

final class PlayerDeathListener implements Listener {

    /**
     * @param PlayerDeathEvent $ev
     *
     * @priority NORMAL
     */
    public function onPlayerDeathEvent(PlayerDeathEvent $ev): void {
        $player = $ev->getPlayer();

        if (($profile = ProfileFactory::getInstance()->getIfLoaded($player->getXuid())) === null) return;

        $faction = $profile->getFactionId() !== null ? FactionFactory::getInstance()->getFaction($profile->getFactionId()) : null;
        if ($faction !== null) {
            $faction->setDeathsUntilRaidable($faction->getDeathsUntilRaidable(true) - 1.0);

            $faction->setRemainingRegenerationTime(FactionFactory::getInstance()->getDtrFreeze() * 60);
            $faction->forceSave(true);

            FactionFactory::getInstance()->storeFactionRegenerating($faction->getId(), $faction->getRegenCooldown());
        }

        if (($profileTimer = $profile->getProfileTimer(ProfileTimer::COMBAT_TAG)) !== null) $profileTimer->cancel();

        $cause = $player->getLastDamageCause();

        $profile->setDeaths($profile->getDeaths() + 1);
        $profile->forceSave(true);

        if (!$cause instanceof EntityDamageByEntityEvent) {
            $ev->setDeathMessage(TextFormat::colorize('&c' . $player->getName() . '&4[' . $profile->getKills() . ']&e died.'));

            return;
        }

        $killer = $cause->getDamager();
        if (!$killer instanceof Player || ($killerProfile = ProfileFactory::getInstance()->getIfLoaded($killer->getXuid())) === null) return;

        $killerProfile->setKills($killerProfile->getKills() + 1);
        $killerProfile->forceSave(true);

        if (($faction = FactionFactory::getInstance()->getPlayerFaction($killer)) !== null) {
            $faction->setPoints($faction->getPoints() + HCFCore::getConfigInt('factions.points-increase-kill', 1));
            $faction->forceSave(true);
        }

        $ev->setDeathMessage(TextFormat::colorize(sprintf('&c%s&4[%s]&e was slain by &c%s&4[%s]&e.',
            $player->getName(),
            $profile->getKills(),
            $killer->getName(),
            $killerProfile->getKills()
        )));
    }
}