<?php

declare(strict_types=1);

use App\Console\Commands\DeleteUsers;
use App\Console\Commands\StageUnverifiedUsersForDeletion;
use Illuminate\Auth\Console\ClearResetsCommand;
use Laravel\Sanctum\Console\Commands\PruneExpired;
use Propaganistas\LaravelDisposableEmail\Console\UpdateDisposableDomainsCommand;

Schedule::command(ClearResetsCommand::class)->daily()->runInBackground();
Schedule::command(PruneExpired::class, ['--hours=24'])->daily()->runInBackground();
Schedule::command(DeleteUsers::class)->daily();
Schedule::command(StageUnverifiedUsersForDeletion::class)->daily();
Schedule::command(UpdateDisposableDomainsCommand::class)->weekly();
