<?php

declare(strict_types=1);

namespace App\Listeners\HealthChecks;

use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Support\Facades\DB;

class HasMySqlConnection
{
    /**
     * Handle the event.
     */
    public function handle(DiagnosingHealth $event): void
    {
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            throw new \RuntimeException('Could not connect to the database: ' . $e->getMessage(), 0, $e);
        }
    }
}
