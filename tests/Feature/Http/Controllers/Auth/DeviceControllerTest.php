<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Laravel\Sanctum\PersonalAccessToken;

test('user can retrieve devices', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->getJson(route('devices'))
        ->assertOk()
        ->assertJsonStructure(['data' => [
            ['device', 'browser', 'hash', 'isCurrent', 'lastUsedAt', 'location']
        ]]);
});
test('user cannot disconnect current device', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $token = $user->currentAccessToken();
    $hash = Crypt::encryptString($token->getKey());

    $this->deleteJson(route('devices.disconnect'), ['hash' => $hash])
        ->assertStatus(422)
        ->assertJson(['message' => __('You cannot disconnect the current device.')]);
});
test('user can disconnect other device', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $hash = Crypt::encryptString(
        $user->createToken('new token')->accessToken->getKey()
    );
    $this->assertDatabaseCount(PersonalAccessToken::class, 2);
    $this->deleteJson(route('devices.disconnect'), ['hash' => $hash])
        ->assertNoContent();
    $this->assertDatabaseCount(PersonalAccessToken::class, 1);
});
