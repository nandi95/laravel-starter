<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withHeader;

test('it requires authentication', function (): void {
    postJson(route('impersonate', 1))->assertUnauthorized();
});

test('user must be admin to impersonate', function (): void {
    $this->setUpRoles();
    $impersonated = User::factory()->create(['first_name' => 'impersonated', 'last_name' => '']);

    /** @var User $impersonator */
    $impersonator = User::factory()->create(['first_name' => 'impersonator', 'last_name' => '']);

    actingAs($impersonator);

    postJson(route('impersonate', $impersonated))
        ->assertForbidden();
});

test('it can successfully impersonate by admin', function (): void {
    $this->setUpRoles();

    /** @var User $impersonated */
    $impersonated = User::factory()->create(['first_name' => 'impersonated', 'last_name' => '']);

    /** @var User $impersonator */
    $impersonator = User::factory()->create(['first_name' => 'impersonator', 'last_name' => '']);

    $impersonator->assignRole(Role::Admin);

    actingAs($impersonator);

    $newToken = postJson(route('impersonate', $impersonated))
        ->assertOk()
        ->json('data');

    /** @var PersonalAccessToken $accessToken */
    $accessToken = $impersonated->tokens()->whereNotNull('impersonator_id')->first();

    expect(PersonalAccessToken::findToken($newToken)->is($accessToken))->toBeTrue();

    getJson(route('user.index', $impersonated))
        ->assertOk()
        ->assertJsonPath('data.first_name', 'impersonated');
});

test('it can stop impersonating', function (): void {
    $this->setUpRoles();

    /** @var User $impersonated */
    $impersonated = User::factory()->create(['first_name' => 'impersonated', 'last_name' => '']);

    /** @var User $impersonator */
    $impersonator = User::factory()->create(['first_name' => 'impersonator', 'last_name' => '']);

    actingAs($impersonator);

    $impersonator->assignRole(Role::Admin);

    $impersonatedToken = $this
        ->postJson(route('impersonate', $impersonated))
        ->json('data');

    $this->app['auth']->forgetUser();
    $oldToken = withHeader('Authorization', "Bearer $impersonatedToken")
        ->deleteJson(route('impersonate.stop'))
        ->assertOk()
        ->json('data');

    $this->token = $oldToken;

    getJson(route('user.index'))
        ->assertOk()
        ->assertJsonPath('data.first_name', $impersonator->first_name);
});
