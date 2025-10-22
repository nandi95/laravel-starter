<?php

declare(strict_types=1);

use App\Enums\AssetType;
use App\Jobs\MoveFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
    Bus::fake();
});
test('can get authenticated user details', function (): void {
    actingAs($this->user)
        ->getJson(route('user.index'))
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'first_name',
                'last_name',
                'email',
                'avatar',
                'unreadNotificationsCount',
                'roles',
                'userProviders'
            ]
        ]);
});
test('can update user profile', function (): void {
    actingAs($this->user)
        ->patchJson(route('user.update'), [
            'first_name' => 'New',
            'last_name' => 'Name',
            'email' => 'newemail@example.com'
        ])
        ->assertOk();

    assertDatabaseHas('users', [
        'id' => $this->user->getKey(),
        'first_name' => 'New',
        'last_name' => 'Name',
        'email' => 'newemail@example.com'
    ]);
});
test('validates profile update request', function (): void {
    actingAs($this->user)
        ->patchJson(route('user.update'), [
            'last_name' => 'Test',
            'email' => 'invalid-email'
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['first_name', 'email']);
});
test('cannot use existing email for profile update', function (): void {
    actingAs($this->user)
        ->patchJson(route('user.update'), [
            'first_name' => 'New',
            'last_name' => 'Name',
            'email' => $this->otherUser->email
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});
test('email verification is reset when email changes', function (): void {
    $this->user->markEmailAsVerified();

    actingAs($this->user)
        ->patchJson(route('user.update'), [
            'first_name' => 'New',
            'last_name' => 'Name',
            'email' => 'newemail@example.com'
        ])
        ->assertOk();

    $this->assertNotInstanceOf(Carbon::class, $this->user->fresh()->email_verified_at);
});
test('can update user avatar', function (): void {
    $file = UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg');

    Storage::shouldReceive('exists')
        ->andReturn(true);
    Storage::shouldReceive('url')
        ->andReturn('http://example.com/tmp/' . $file->name);

    actingAs($this->user)
        ->postJson(route('user.avatar'), [
            'key' => 'tmp/' . $file->name,
            'title' => 'Avatar',
            'size' => $file->getSize(),
            'width' => 800,
            'height' => 600,
        ])
        ->assertOk()
        ->assertJsonStructure([
            'data'
        ]);

    assertDatabaseHas('users', [
        'id' => $this->user->getKey(),
        'avatar' => 'user/' . $this->user->ulid . '/' . AssetType::Image->value . 's/' . $file->name
    ]);

    Bus::assertDispatched(MoveFile::class);
});
test('deletes old avatar when updating', function (): void {
    $oldPath = 'avatars/old-avatar.jpg';
    $this->user->update(['avatar' => $oldPath]);

    $file = UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg');

    Storage::shouldReceive('exists')
        ->andReturn(true);
    Storage::shouldReceive('url')
        ->andReturn('http://example.com/tmp/' . $file->name);

    actingAs($this->user)
        ->postJson(route('user.avatar'), [
            'key' => 'tmp/' . $file->name,
            'title' => 'Avatar',
            'size' => $file->getSize(),
            'width' => 800,
            'height' => 600,
        ])
        ->assertOk();

    Bus::assertDispatched(\App\Jobs\DeleteFile::class, fn ($job): bool => $job->path === $oldPath);
});
test('validates avatar update request', function (): void {
    actingAs($this->user)
        ->postJson(route('user.avatar'), ['size' => 123, 'width' => 800, 'height' => 600])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['key']);
});
test('requires authentication for all routes', function (): void {
    getJson(route('user.index'))->assertUnauthorized();
    patchJson(route('user.update'))->assertUnauthorized();
    postJson(route('user.avatar'))->assertUnauthorized();
});
