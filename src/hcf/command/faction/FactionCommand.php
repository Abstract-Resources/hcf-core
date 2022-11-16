<?php

declare(strict_types=1);

namespace hcf\command\faction;

use abstractplugin\command\BaseCommand;
use hcf\command\faction\arguments\CreateArgument;
use hcf\command\faction\arguments\JoinArgument;
use hcf\command\faction\arguments\leader\ClaimArgument;
use hcf\command\faction\arguments\leader\DisbandArgument;
use hcf\command\faction\arguments\officer\InviteArgument;

final class FactionCommand extends BaseCommand {

    public function __construct() {
        parent::__construct('faction', 'Faction management', '/f help', ['f']);

        $this->registerParent(
            new CreateArgument('create'),
            new InviteArgument('invite'),
            new ClaimArgument('claim'),
            new DisbandArgument('disband'),
            new JoinArgument('join')
        );
    }
}