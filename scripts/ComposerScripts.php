<?php

namespace Scripts;

use Composer\Script\Event;

class ComposerScripts
{
    /**
     * Run scripts that follow only if dev packages are installed.
     */
    public static function devModeOnly(Event $event): void
    {
        if ($event->isDevMode()) {
            $event->stopPropagation();
            echo "Skipping {$event->getName()} as this is a non-dev installation.\n";
        }
    }
}
