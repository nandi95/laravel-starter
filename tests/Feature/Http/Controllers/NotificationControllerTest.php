<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $otherUser;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
    }

    public function test_can_list_notifications(): void
    {
        // Create some notifications for the user
        $this->user->notifications()->create([
            'id' => 'test-notification-1',
            'type' => 'App\Notifications\TestNotification',
            'data' => ['message' => 'Test notification 1'],
        ]);

        $this->user->notifications()->create([
            'id' => 'test-notification-2',
            'type' => 'App\Notifications\TestNotification',
            'data' => ['message' => 'Test notification 2'],
        ]);

        $this->actingAs($this->user)
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
    }

    public function test_can_list_notifications_with_custom_per_page(): void
    {
        // Create 15 notifications
        for ($i = 0; $i < 15; $i++) {
            $this->user->notifications()->create([
                'id' => "test-notification-{$i}",
                'type' => 'App\Notifications\TestNotification',
                'data' => ['message' => "Test notification {$i}"],
            ]);
        }

        $this->actingAs($this->user)
            ->getJson(route('notifications.index', ['per_page' => 5]))
            ->assertOk()
            ->assertJsonCount(5, 'data');
    }

    public function test_can_mark_all_notifications_as_read(): void
    {
        // Create some unread notifications
        $this->user->notifications()->create([
            'id' => 'test-notification-1',
            'type' => 'App\Notifications\TestNotification',
            'data' => ['message' => 'Test notification 1'],
        ]);

        $this->user->notifications()->create([
            'id' => 'test-notification-2',
            'type' => 'App\Notifications\TestNotification',
            'data' => ['message' => 'Test notification 2'],
        ]);

        $this->actingAs($this->user)
            ->postJson(route('notifications.mark-all-read'))
            ->assertNoContent();

        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $this->user->getKey(),
            'readAt' => null,
        ]);
    }

    public function test_can_mark_single_notification_as_read(): void
    {
        $notification = $this->user->notifications()->create([
            'id' => 'test-notification',
            'type' => 'App\Notifications\TestNotification',
            'data' => ['message' => 'Test notification'],
        ]);

        $this->actingAs($this->user)
            ->patchJson(route('notifications.mark-read', $notification))
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'data',
                'readAt',
                'createdAt',
            ]);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_cannot_mark_other_users_notification_as_read(): void
    {
        $notification = $this->otherUser->notifications()->create([
            'id' => 'test-notification',
            'type' => 'App\Notifications\TestNotification',
            'data' => ['message' => 'Test notification'],
        ]);

        $this->actingAs($this->user)
            ->patchJson(route('notifications.mark-read', $notification))
            ->assertForbidden();
    }

    public function test_can_mark_single_notification_as_unread(): void
    {
        $notification = $this->user->notifications()->create([
            'id' => 'test-notification',
            'type' => 'App\Notifications\TestNotification',
            'data' => ['message' => 'Test notification'],
            'read_at' => now(),
        ]);

        $this->actingAs($this->user)
            ->patchJson(route('notifications.mark-unread', $notification))
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'data',
                'readAt',
                'createdAt',
            ]);

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_cannot_mark_other_users_notification_as_unread(): void
    {
        $notification = $this->otherUser->notifications()->create([
            'id' => 'test-notification',
            'type' => 'App\Notifications\TestNotification',
            'data' => ['message' => 'Test notification'],
            'read_at' => now(),
        ]);

        $this->actingAs($this->user)
            ->patchJson(route('notifications.mark-unread', $notification))
            ->assertForbidden();
    }
}
