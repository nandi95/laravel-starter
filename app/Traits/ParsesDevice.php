<?php

declare(strict_types=1);

namespace App\Traits;

use DeviceDetector\ClientHints;
use DeviceDetector\DeviceDetector;

trait ParsesDevice
{
    protected function getDeviceName(): string
    {
        $dd = new DeviceDetector(request()?->userAgent(), ClientHints::factory(request()?->server()));
        $dd->parse();

        return $dd->getDeviceName() . ' / ' . $dd->getClient('name') . ' ' . $dd->getClient('version');
    }
}
