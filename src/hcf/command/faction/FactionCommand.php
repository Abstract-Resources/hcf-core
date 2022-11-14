<?php

declare(strict_types=1);

namespace hcf\command\faction;

use abstractplugin\command\BaseCommand;
use hcf\command\faction\arguments\CreateArgument;
use hcf\command\faction\arguments\DisbandArgument;

final class FactionCommand extends BaseCommand {

    public function __construct() {
        parent::__construct('faction', 'Faction management', '/f help', ['f']);

        $this->registerParent(
            new CreateArgument('create'),
            new DisbandArgument('disband')
        );
    }
}