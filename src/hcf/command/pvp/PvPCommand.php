<?php

declare(strict_types=1);

namespace hcf\command\pvp;

use abstractplugin\command\BaseCommand;
use hcf\command\pvp\arguments\EnableArgument;

final class PvPCommand extends BaseCommand {

    public function __construct() {
        parent::__construct('pvp', 'Enables you the pvp', '/pvp enable');

        $this->registerParent(new EnableArgument('enable'));
    }
}