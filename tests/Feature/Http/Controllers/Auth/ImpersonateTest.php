<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

test('it requires authentication', function (): void {
    $this->postJson(route('impersonate', 1))->assertUnauthorized();
});

test('user must be admin to impersonate', function (): void {
    $this->setUpRoles();
    $impersonated = User::factory()->create(['first_name' => 'impersonated', 'last_name' => '']);

    /** @var User $impersonator */
    $impersonator = User::factory()->create(['first_name' => 'impersonator', 'last_name' => '']);

    $this->actingAs($impersonator);

    $this->postJson(route('impersonate', $impersonated))
        ->assertForbidden();
});

test('it can successfully impersonate by admin', function (): void {
    $this->setUpRoles();

    /** @var User $impersonated */
    $impersonated = User::factory()->create(['first_name' => 'impersonated', 'last_name' => '']);

    /** @var User $impersonator */
    $impersonator = User::factory()->create(['first_name' => 'impersonator', 'last_name' => '']);

    $impersonator->assignRole(Role::Admin);

    $this->actingAs($impersonator);

    $newToken = $this->postJson(route('impersonate', $impersonated))
        ->assertOk()
        ->json('data');

    /** @var PersonalAccessToken $accessToken */
    $accessToken = $impersonated->tokens()->whereNotNull('impersonator_id')->first();

    expect(PersonalAccessToken::findToken($newToken)->is($accessToken))->toBeTrue();

    $this->getJson(route('user.index', $impersonated))
        ->assertOk()
        ->assertJsonPath('data.first_name', 'impersonated');
});

test('it can stop impersonating', function (): void {
    $this->setUpRoles();

    /** @var User $impersonated */
    $impersonated = User::factory()->create(['first_name' => 'impersonated', 'last_name' => '']);

    /** @var User $impersonator */
    $impersonator = User::factory()->create(['first_name' => 'impersonator', 'last_name' => '']);

    $this->actingAs($impersonator);

    $impersonator->assignRole(Role::Admin);

    $impersonatedToken = $this
        ->postJson(route('impersonate', $impersonated))
        ->json('data');

    $this->app['auth']->forgetUser();
    $oldToken = $this->withHeader('Authorization', "Bearer $impersonatedToken")
        ->deleteJson(route('impersonate.stop'))
        ->assertOk()
        ->json('data');

    $this->token = $oldToken;

    $this->getJson(route('user.index'))
        ->assertOk()
        ->assertJsonPath('data.first_name', $impersonator->first_name);
});
