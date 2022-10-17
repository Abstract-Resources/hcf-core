<?php

declare(strict_types=1);

namespace hcf\command\faction;

use hcf\command\BaseCommand;
use hcf\command\faction\arguments\CreateArgument;

final class FactionCommand extends BaseCommand {

    public function __construct() {
        parent::__construct('faction', 'Faction management', '/f help', ['f']);

        $this->registerParent(
            new CreateArgument('create')
        );
    }
}