<?php

declare(strict_types=1);

use App\Console\Commands\DeleteUsers;
use App\Jobs\DeleteFile;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;

test('it should check argument type', function (): void {
    $this->artisan(DeleteUsers::class, ['--days' => 'not numeric'])
        ->assertExitCode(DeleteUsers::INVALID);
});
test('it should delete users and related resources', function (): void {
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
});
test('it should only delete specific users', function (): void {
    $users = User::factory()->count(3)->create([
        'deleted_at' => now()->subDays(60),
    ]);

    $this->assertDatabaseCount(User::class, 3);

    $this->artisan(DeleteUsers::class, ['user' => [$users->modelKeys()[0], $users->modelKeys()[1]]])
        ->assertExitCode(DeleteUsers::SUCCESS);

    $this->assertDatabaseCount(User::class, 1);
    $this->assertDatabaseHas(User::class, ['id' => $users->modelKeys()[2]]);
});
test('storage deletion is queued', function (): void {
    Queue::fake();
    User::factory()->create([
        'deleted_at' => now()->subDays(60),
        'avatar' => 'avatar.jpg',
    ]);

    $this->artisan(DeleteUsers::class)->assertExitCode(DeleteUsers::SUCCESS);

    Queue::assertPushed(DeleteFile::class, 1);
});
