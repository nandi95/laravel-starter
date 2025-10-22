<?php

declare(strict_types=1);

namespace Tests;

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{
    use LazilyRefreshDatabase;

    protected ?string $token = null;

    /**
     * @param  User  $user
     */
    #[\Override]
    public function actingAs(UserContract $user, $guard = null): static
    {
        if ($this->token === null) {
            $this->token = $user->createToken('test-token')->plainTextToken;
        }

        /** @var PersonalAccessToken $pat */
        $pat = PersonalAccessToken::findToken($this->token);

        $user->withAccessToken($pat);
        $this->withHeader('Authorization', 'Bearer ' . $this->token);

        return $this;
    }

    /**
     * @param  'sanctum'|'web'  $forGuard
     */
    public function setupRoles(string $forGuard = 'web'): void
    {
        Role::insert(
            array_map(static fn (RoleEnum $role): array => ['name' => $role->value, 'guard_name' => $forGuard], RoleEnum::cases())
        );
    }
}
