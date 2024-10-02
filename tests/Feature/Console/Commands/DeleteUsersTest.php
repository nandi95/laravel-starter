<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Console\Commands\DeleteUsers;
use App\Jobs\DeleteFile;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class DeleteUsersTest extends TestCase
{
    public function test_it_should_check_argument_type(): void
    {
        $this->artisan(DeleteUsers::class, ['--days' => 'not numeric'])
            ->assertExitCode(DeleteUsers::INVALID);
    }

    public function test_it_should_delete_users_and_related_resources(): void
    {
        Storage::fake();
        $user = User::factory()->create([
            'deleted_at' => now()->subDays(60),
            'avatar' => 'avatar.jpg',
        ]);
        Storage::put('avatar.jpg', 'avatar.jpg');
        $user->createToken('test-token');

        $this->assertDatabaseCount(PersonalAccessToken::class, 1);
        Storage::assertExists('avatar.jpg');
        $this->assertDatabaseCount(User::class, 1);

        $this->artisan(DeleteUsers::class)->assertExitCode(DeleteUsers::SUCCESS);

        $this->assertDatabaseEmpty(User::class);
        Storage::assertMissing('avatar.jpg');
        $this->assertDatabaseEmpty(PersonalAccessToken::class);
    }

    public function test_it_should_only_delete_specific_users(): void
    {
        $users = User::factory()->count(3)->create([
            'deleted_at' => now()->subDays(60),
        ]);

        $this->assertDatabaseCount(User::class, 3);

        $this->artisan(DeleteUsers::class, ['user' => [$users->modelKeys()[0], $users->modelKeys()[1]]])
            ->assertExitCode(DeleteUsers::SUCCESS);

        $this->assertDatabaseCount(User::class, 1);
        $this->assertDatabaseHas(User::class, ['id' => $users->modelKeys()[2]]);
    }

    public function test_storage_deletion_is_queued(): void
    {
        Queue::fake();
        User::factory()->create([
            'deleted_at' => now()->subDays(60),
            'avatar' => 'avatar.jpg',
        ]);

        $this->artisan(DeleteUsers::class)->assertExitCode(DeleteUsers::SUCCESS);

        Queue::assertPushed(DeleteFile::class, 1);
    }
}
