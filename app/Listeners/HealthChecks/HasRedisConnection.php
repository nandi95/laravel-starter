<?php

declare(strict_types=1);

namespace App\Listeners\HealthChecks;

use App\Exceptions\HealthCheckException;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Support\Facades\Redis;

class HasRedisConnection
{
    /**
     * Handle the event.
     *
     * @throws HealthCheckException
     */
    public function handle(DiagnosingHealth $event): void
    {
        try {
            Redis::connection()->ping();
        } catch (\Exception $e) {
            throw new HealthCheckException('Could not connect to Redis: ' . $e->getMessage(), 0, $e);
        }
    }
}
