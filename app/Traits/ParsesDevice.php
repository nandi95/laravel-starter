<?php

declare(strict_types=1);

namespace App\Traits;

use DeviceDetector\ClientHints;
use DeviceDetector\DeviceDetector;

trait ParsesDevice
{
    protected function getDeviceName(): string
    {
        $deviceDetector = new DeviceDetector(request()?->userAgent(), ClientHints::factory(request()?->server()));
        $deviceDetector->parse();

        return $deviceDetector->getDeviceName() . ' / ' . $deviceDetector->getClient('name') . ' ' . $deviceDetector->getClient('version');
    }
}
