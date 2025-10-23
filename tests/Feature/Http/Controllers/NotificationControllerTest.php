<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
});
test('can list notifications', function (): void {
    // Create some notifications for the user
    $this->user->notifications()->create([
        'id' => Str::uuid()->toString(),
        'type' => 'App\Notifications\TestNotification',
        'data' => ['message' => 'Test notification 1'],
    ]);

    $this->user->notifications()->create([
        'id' => Str::uuid()->toString(),
        'type' => 'App\Notifications\TestNotification',
        'data' => ['message' => 'Test notification 2'],
    ]);

    actingAs($this->user)
        ->getJson(route('notifications.index'))
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'data',
                    'readAt',
                    'createdAt',
                ]
            ],
            'meta',
            'links'
        ]);
});
test('can list notifications with custom per page', function (): void {
    // Create 15 notifications
    for ($i = 0; $i < 15; $i++) {
        $this->user->notifications()->create([
            'id' => Str::uuid()->toString(),
            'type' => 'App\Notifications\TestNotification',
            'data' => ['message' => "Test notification {$i}"],
        ]);
    }

    actingAs($this->user)
        ->getJson(route('notifications.index', ['per_page' => 5]))
        ->assertOk()
        ->assertJsonCount(5, 'data');
});
test('can mark all notifications as read', function (): void {
    // Create some unread notifications
    $this->user->notifications()->create([
        'id' => Str::uuid()->toString(),
        'type' => 'App\Notifications\TestNotification',
        'data' => ['message' => 'Test notification 1'],
    ]);

    $this->user->notifications()->create([
        'id' => Str::uuid()->toString(),
        'type' => 'App\Notifications\TestNotification',
        'data' => ['message' => 'Test notification 2'],
    ]);

    actingAs($this->user)
        ->postJson(route('notifications.mark-all-read'))
        ->assertNoContent();

    assertDatabaseMissing('notifications', [
        'notifiable_id' => $this->user->getKey(),
        'read_at' => null,
    ]);
});
test('can mark single notification as read', function (): void {
    $notification = $this->user->notifications()->create([
        'id' => Str::uuid()->toString(),
        'type' => 'App\Notifications\TestNotification',
        'data' => ['message' => 'Test notification'],
    ]);

    actingAs($this->user)
        ->patchJson(route('notifications.mark-read', $notification))
        ->assertOk()
        ->assertJsonStructure([
            'id',
            'data',
            'readAt',
            'createdAt',
        ]);

    expect($notification->fresh()->read_at)->not->toBeNull();
});
test('cannot mark other users notification as read', function (): void {
    $notification = $this->otherUser->notifications()->create([
        'id' => Str::uuid()->toString(),
        'type' => 'App\Notifications\TestNotification',
        'data' => ['message' => 'Test notification'],
    ]);

    actingAs($this->user)
        ->patchJson(route('notifications.mark-read', $notification))
        ->assertForbidden();
});
test('can mark single notification as unread', function (): void {
    $notification = $this->user->notifications()->create([
        'id' => Str::uuid()->toString(),
        'type' => 'App\Notifications\TestNotification',
        'data' => ['message' => 'Test notification'],
        'read_at' => now(),
    ]);

    actingAs($this->user)
        ->patchJson(route('notifications.mark-unread', $notification))
        ->assertOk()
        ->assertJsonStructure([
            'id',
            'data',
            'readAt',
            'createdAt',
        ]);

    expect($notification->fresh()->read_at)->toBeNull();
});
test('cannot mark other users notification as unread', function (): void {
    $notification = $this->otherUser->notifications()->create([
        'id' => Str::uuid()->toString(),
        'type' => 'App\Notifications\TestNotification',
        'data' => ['message' => 'Test notification'],
        'read_at' => now(),
    ]);

    actingAs($this->user)
        ->patchJson(route('notifications.mark-unread', $notification))
        ->assertForbidden();
});
