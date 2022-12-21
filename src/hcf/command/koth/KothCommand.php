<?php

declare(strict_types=1);

namespace hcf\command\koth;

use abstractplugin\command\BaseCommand;
use hcf\command\koth\arguments\StartArgument;

final class KothCommand extends BaseCommand {

    public function __construct() {
        parent::__construct('koth', 'Koth admin management');

        $this->registerParent(
            new StartArgument('start', 'koth.start')
        );
    }
}