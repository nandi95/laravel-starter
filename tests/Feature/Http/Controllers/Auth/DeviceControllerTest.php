<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Laravel\Sanctum\PersonalAccessToken;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;

test('user can retrieve devices', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    getJson(route('devices'))
        ->assertOk()
        ->assertJsonStructure(['data' => [
            ['device', 'browser', 'hash', 'isCurrent', 'lastUsedAt', 'location']
        ]]);
});
test('user cannot disconnect current device', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    $token = $user->currentAccessToken();
    $hash = Crypt::encryptString($token->getKey());

    deleteJson(route('devices.disconnect'), ['hash' => $hash])
        ->assertStatus(422)
        ->assertJson(['message' => __('You cannot disconnect the current device.')]);
});
test('user can disconnect other device', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    $hash = Crypt::encryptString(
        $user->createToken('new token')->accessToken->getKey()
    );
    assertDatabaseCount(PersonalAccessToken::class, 2);
    deleteJson(route('devices.disconnect'), ['hash' => $hash])
        ->assertNoContent();
    assertDatabaseCount(PersonalAccessToken::class, 1);
});
