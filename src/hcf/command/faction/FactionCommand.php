<?php

declare(strict_types=1);

namespace hcf\command\faction;

use abstractplugin\command\BaseCommand;
use hcf\command\faction\arguments\CreateArgument;
use hcf\command\faction\arguments\JoinArgument;
use hcf\command\faction\arguments\leader\ClaimArgument;
use hcf\command\faction\arguments\leader\DisbandArgument;
use hcf\command\faction\arguments\member\DepositArgument;
use hcf\command\faction\arguments\member\LeaveArgument;
use hcf\command\faction\arguments\officer\InviteArgument;
use hcf\command\faction\arguments\WhoArgument;

final class FactionCommand extends BaseCommand {

    public function __construct() {
        parent::__construct('faction', 'Faction management', '/f help', ['f']);

        $this->registerParent(
            new CreateArgument('create'),
            new InviteArgument('invite'),
            new ClaimArgument('claim'),
            new DisbandArgument('disband'),
            new DepositArgument('deposit'),
            new LeaveArgument('leave'),
            new JoinArgument('join'),
            new WhoArgument('who')
        );
    }
}