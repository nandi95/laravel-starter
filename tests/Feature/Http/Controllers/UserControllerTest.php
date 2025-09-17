<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Enums\AssetType;
use App\Jobs\MoveFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    private User $user;

    private User $otherUser;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        Bus::fake();
    }

    public function test_can_get_authenticated_user_details(): void
    {
        $this->actingAs($this->user)
            ->getJson(route('user.index'))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'avatar',
                    'unreadNotificationsCount',
                    'roles',
                    'userProviders'
                ]
            ]);
    }

    public function test_can_update_user_profile(): void
    {
        $this->actingAs($this->user)
            ->patchJson(route('user.update'), [
                'name' => 'New Name',
                'email' => 'newemail@example.com'
            ])
            ->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $this->user->getKey(),
            'name' => 'New Name',
            'email' => 'newemail@example.com'
        ]);
    }

    public function test_validates_profile_update_request(): void
    {
        $this->actingAs($this->user)
            ->patchJson(route('user.update'), [
                'name' => 'a', // Too short
                'email' => 'invalid-email'
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email']);
    }

    public function test_cannot_use_existing_email_for_profile_update(): void
    {
        $this->actingAs($this->user)
            ->patchJson(route('user.update'), [
                'name' => 'New Name',
                'email' => $this->otherUser->email
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_email_verification_is_reset_when_email_changes(): void
    {
        $this->user->markEmailAsVerified();

        $this->actingAs($this->user)
            ->patchJson(route('user.update'), [
                'name' => 'New Name',
                'email' => 'newemail@example.com'
            ])
            ->assertOk();

        $this->assertNotInstanceOf(\Illuminate\Support\Carbon::class, $this->user->fresh()->email_verified_at);
    }

    public function test_can_update_user_avatar(): void
    {
        $file = UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg');

        Storage::shouldReceive('exists')
            ->andReturn(true);
        Storage::shouldReceive('url')
            ->andReturn('http://example.com/tmp/' . $file->name);

        $this->actingAs($this->user)
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

        $this->assertDatabaseHas('users', [
            'id' => $this->user->getKey(),
            'avatar' => 'user/' . $this->user->ulid . '/' . AssetType::Image->value . 's/' . $file->name
        ]);

        Bus::assertDispatched(MoveFile::class);
    }

    public function test_deletes_old_avatar_when_updating(): void
    {
        $oldPath = 'avatars/old-avatar.jpg';
        $this->user->update(['avatar' => $oldPath]);

        $file = UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg');

        Storage::shouldReceive('exists')
            ->andReturn(true);
        Storage::shouldReceive('url')
            ->andReturn('http://example.com/tmp/' . $file->name);

        $this->actingAs($this->user)
            ->postJson(route('user.avatar'), [
                'key' => 'tmp/' . $file->name,
                'title' => 'Avatar',
                'size' => $file->getSize(),
                'width' => 800,
                'height' => 600,
            ])
            ->assertOk();

        Bus::assertDispatched(\App\Jobs\DeleteFile::class, fn ($job): bool => $job->path === $oldPath);
    }

    public function test_validates_avatar_update_request(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('user.avatar'), ['size' => 123, 'width' => 800, 'height' => 600])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['key']);
    }

    public function test_requires_authentication_for_all_routes(): void
    {
        $this->getJson(route('user.index'))->assertUnauthorized();
        $this->patchJson(route('user.update'))->assertUnauthorized();
        $this->postJson(route('user.avatar'))->assertUnauthorized();
    }
}
