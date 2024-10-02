<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class DeviceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_retrieve_devices(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->getJson(route('devices'))
            ->assertOk()
            ->assertJsonStructure(['data' => [
                ['device', 'browser', 'hash', 'isCurrent', 'lastUsedAt', 'location']
            ]]);
    }

    public function test_user_cannot_disconnect_current_device(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $token = $user->currentAccessToken();
        $hash = Crypt::encryptString($token->getKey());

        $this->deleteJson(route('devices.disconnect'), ['hash' => $hash])
            ->assertStatus(422)
            ->assertJson(['message' => __('You cannot disconnect the current device.')]);
    }

    public function test_user_can_disconnect_other_device(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $hash = Crypt::encryptString(
            $user->createToken('new token')->accessToken->getKey()
        );
        $this->assertDatabaseCount(PersonalAccessToken::class, 2);
        $this->deleteJson(route('devices.disconnect'), ['hash' => $hash])
            ->assertNoContent();
        $this->assertDatabaseCount(PersonalAccessToken::class, 1);
    }
}
