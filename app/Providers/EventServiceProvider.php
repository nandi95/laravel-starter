<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\HealthChecks\HasDatabaseConnection;
use App\Listeners\HealthChecks\HasRedisConnection;
use App\Listeners\HealthChecks\HasSufficientDiskSpace;
use App\Listeners\RecordRequestIdentifiers;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Laravel\Sanctum\Events\TokenAuthenticated;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        TokenAuthenticated::class => [
            RecordRequestIdentifiers::class
        ],
        DiagnosingHealth::class => [
            HasDatabaseConnection::class,
            HasRedisConnection::class,
            HasSufficientDiskSpace::class
        ],
    ];

    /**
     * Register services.
     */
    #[\Override]
    public function register(): void
    {
        parent::register();
        //
    }

    /**
     * Bootstrap services.
     */
    #[\Override]
    public function boot(): void
    {
        //
    }
}
