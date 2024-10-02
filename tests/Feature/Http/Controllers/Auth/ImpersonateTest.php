<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use App\Enums\Role;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class ImpersonateTest extends TestCase
{
    public function test_it_requires_authentication(): void
    {
        $this->postJson(route('impersonate', 1))->assertUnauthorized();
    }

    public function test_user_must_be_admin_to_impersonate(): void
    {
        $this->setUpRoles();
        $impersonated = User::factory()->create(['name' => 'impersonated']);
        /** @var User $impersonator */
        $impersonator = User::factory()->create(['name' => 'impersonator']);

        $this->actingAs($impersonator);

        $this->postJson(route('impersonate', $impersonated))
            ->assertForbidden();
    }

    public function test_it_can_successfully_impersonate_by_admin(): void
    {
        $this->withoutExceptionHandling();
        $this->setUpRoles();
        /** @var User $impersonated */
        $impersonated = User::factory()->create(['name' => 'impersonated']);
        /** @var User $impersonator */
        $impersonator = User::factory()->create(['name' => 'impersonator']);

        $impersonator->assignRole(Role::Admin);

        $this->actingAs($impersonator);

        $newToken = $this->postJson(route('impersonate', $impersonated))
            ->assertOk()
            ->json('data');

        /** @var PersonalAccessToken $accessToken */
        $accessToken = $impersonated->tokens()->whereNotNull('impersonator_id')->first();

        $this->assertTrue(PersonalAccessToken::findToken($newToken)->is($accessToken));

        $this->getJson(route('user.index', $impersonated))
            ->assertOk()
            ->assertJsonPath('data.name', 'impersonated');
    }

    public function test_it_can_stop_impersonating(): void
    {
        $this->withoutExceptionHandling();
        $this->setUpRoles();
        /** @var User $impersonated */
        $impersonated = User::factory()->create(['name' => 'impersonated']);
        /** @var User $impersonator */
        $impersonator = User::factory()->create(['name' => 'impersonator']);

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
            ->assertJsonPath('data.name', $impersonator->name);
    }

    /**
     * Create roles for the application.
     */
    #[\Override]
    public function setUpRoles(string $forGuard = 'web'): void
    {
        $guard = config('auth.defaults.guard');

        \Spatie\Permission\Models\Role::insert(
            array_map(static fn (Role $role): array => ['name' => $role, 'guard_name' => $guard], Role::cases())
        );
    }
}
