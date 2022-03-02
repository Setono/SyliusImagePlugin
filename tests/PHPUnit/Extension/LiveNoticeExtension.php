<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusImagePlugin\PHPUnit\Extension;

use PHPUnit\Runner\BeforeFirstTestHook;

final class LiveNoticeExtension implements BeforeFirstTestHook
{
    public function executeBeforeFirstTest(): void
    {
        $live = (bool) getenv('CLOUDFLARE_LIVE');
        if (!$live) {
            return;
        }

        echo "\n\n";
        echo "#########################################################\n";
        echo "# YOU ARE RUNNING LIVE TESTS AGAINST THE CLOUDFLARE API #\n";
        echo "#########################################################\n\n";
    }
}
