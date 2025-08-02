<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use App\Notifications\UserHasNoVerification;
use Illuminate\Console\Command;

class StageUnverifiedUsersForDeletion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:stage-unverified-users-for-deletion';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Users who did not verify their email within a certain time frame are staged for deletion';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        User::whereNull('email_verified_at')
            ->where('created_at', '<=', now()->subDays(User::VERIFICATION_DELAY))
            ->each(function (User $user): void {
                $user->notify(new UserHasNoVerification);
                $user->delete();
            });
    }
}
