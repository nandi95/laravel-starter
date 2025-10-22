<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\DeleteFile;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Throwable;

class DeleteUsersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-users
                                {user?* : The IDs of the users to delete}
                                {--days=' . User::DELETION_DELAY . ' : The number of days before a user is deleted}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete users that are staged for deletion';

    /**
     * Execute the console command.
     *
     * @throws Throwable
     */
    public function handle(): int
    {
        if (!is_numeric($this->option('days'))) {
            $this->error('Invalid days argument, must be an integer.');

            return self::INVALID;
        }

        $userCount = 0;

        User::onlyTrashed()
            ->where('deleted_at', '<=', now()->subDays((int) $this->option('days')))
            ->when(!empty($this->argument('user')), function (Builder $query): void {
                $query->whereIn('id', $this->argument('user'));
            })
            ->each(function (User $user) use (&$userCount): void {
                DB::transaction(static function () use ($user, &$userCount): void {
                    $user->tokens()->delete();

                    if ($user->avatar) {
                        DeleteFile::dispatch($user->avatar);
                    }

                    $user->forceDelete();
                    $userCount++;
                });
            });

        $this->info("Deleted {$userCount} users.");

        return self::SUCCESS;
    }
}
