<?php

declare(strict_types=1);

namespace hcf\command\faction;

use abstractplugin\command\BaseCommand;
use hcf\command\faction\arguments\admin\AdminClaimArgument;
use hcf\command\faction\arguments\admin\DisbandAllArgument;
use hcf\command\faction\arguments\admin\ForceDisbandArgument;
use hcf\command\faction\arguments\admin\PurgeArgument;
use hcf\command\faction\arguments\admin\SetBalanceArgument;
use hcf\command\faction\arguments\admin\SotwArgument;
use hcf\command\faction\arguments\CreateArgument;
use hcf\command\faction\arguments\HelpArgument;
use hcf\command\faction\arguments\JoinArgument;
use hcf\command\faction\arguments\leader\ClaimArgument;
use hcf\command\faction\arguments\leader\DemoteArgument;
use hcf\command\faction\arguments\leader\DisbandArgument;
use hcf\command\faction\arguments\leader\PromoteArgument;
use hcf\command\faction\arguments\leader\TransferArgument;
use hcf\command\faction\arguments\leader\UnclaimArgument;
use hcf\command\faction\arguments\ListArgument;
use hcf\command\faction\arguments\member\DepositArgument;
use hcf\command\faction\arguments\member\HomeArgument;
use hcf\command\faction\arguments\member\LeaveArgument;
use hcf\command\faction\arguments\officer\InviteArgument;
use hcf\command\faction\arguments\officer\KickArgument;
use hcf\command\faction\arguments\officer\SetHomeArgument;
use hcf\command\faction\arguments\TopArgument;
use hcf\command\faction\arguments\WhoArgument;

final class FactionCommand extends BaseCommand {

    public function __construct() {
        parent::__construct('faction', 'Faction management', '/f help', ['f']);

        $this->registerParent(
            new CreateArgument('create'),
            new HelpArgument('help'),
            new InviteArgument('invite'),
            new ClaimArgument('claim'),
            new UnclaimArgument('unclaim'),
            new SetHomeArgument('sethome'),
            new PromoteArgument('promote'),
            new DemoteArgument('demote'),
            new TransferArgument('transfer'),
            new DisbandArgument('disband'),
            new DepositArgument('deposit'),
            new LeaveArgument('leave'),
            new JoinArgument('join'),
            new WhoArgument('who'),
            new ListArgument('list'),
            new TopArgument('top'),
            new KickArgument('kick'),
            new HomeArgument('home'),
            new AdminClaimArgument('adminclaim', 'faction.admin'),
            new SetBalanceArgument('setbalance', 'faction.admin'),
            new SotwArgument('sotw', 'faction.admin'),
            new PurgeArgument('purge', 'faction.admin'),
            new ForceDisbandArgument('forcedisband', 'faction.admin'),
            new DisbandAllArgument('disbandall', 'faction.admin')
        );
    }
}