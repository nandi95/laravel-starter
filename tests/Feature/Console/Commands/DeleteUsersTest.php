<?php

declare(strict_types=1);

use App\Console\Commands\DeleteUsersCommand;
use App\Jobs\DeleteFile;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;

use function Pest\Laravel\artisan;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseEmpty;
use function Pest\Laravel\assertDatabaseHas;

test('it should check argument type', function (): void {
    artisan(DeleteUsersCommand::class, ['--days' => 'not numeric'])
        ->assertExitCode(DeleteUsersCommand::INVALID);
});
test('it should delete users and related resources', function (): void {
    Storage::fake();
    $user = User::factory()->create([
        'deleted_at' => now()->subDays(60),
        'avatar' => 'avatar.jpg',
    ]);
    Storage::put('avatar.jpg', 'avatar.jpg');
    $user->createToken('test-token');

    assertDatabaseCount(PersonalAccessToken::class, 1);
    Storage::assertExists('avatar.jpg');
    assertDatabaseCount(User::class, 1);

    artisan(DeleteUsersCommand::class)->assertExitCode(DeleteUsersCommand::SUCCESS);

    assertDatabaseEmpty(User::class);
    Storage::assertMissing('avatar.jpg');
    assertDatabaseEmpty(PersonalAccessToken::class);
});
test('it should only delete specific users', function (): void {
    $users = User::factory()->count(3)->create([
        'deleted_at' => now()->subDays(60),
    ]);

    assertDatabaseCount(User::class, 3);

    artisan(DeleteUsersCommand::class, ['user' => [$users->modelKeys()[0], $users->modelKeys()[1]]])
        ->assertExitCode(DeleteUsersCommand::SUCCESS);

    assertDatabaseCount(User::class, 1);
    assertDatabaseHas(User::class, ['id' => $users->modelKeys()[2]]);
});
test('storage deletion is queued', function (): void {
    Queue::fake();
    User::factory()->create([
        'deleted_at' => now()->subDays(60),
        'avatar' => 'avatar.jpg',
    ]);

    artisan(DeleteUsersCommand::class)->assertExitCode(DeleteUsersCommand::SUCCESS);

    Queue::assertPushed(DeleteFile::class, 1);
});
