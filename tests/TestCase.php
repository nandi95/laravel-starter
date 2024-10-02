<?php

declare(strict_types=1);

namespace Tests;

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
    public function actingAs(UserContract $user, $guard = null)
    {
        if ($this->token === null) {
            $this->token = $user->createToken('test-token')->plainTextToken;
        }

        $user->withAccessToken(PersonalAccessToken::findToken($this->token));
        $this->withHeader('Authorization', 'Bearer ' . $this->token);

        return $this;
    }

    /**
     * @param  'sanctum'|'web'  $forGuard
     */
    public function setupRoles(string $forGuard = 'web'): void
    {
        Role::query()->firstOrCreate(['name' => \App\Enums\Role::Admin, 'guard_name' => $forGuard]);
        Role::query()->firstOrCreate(['name' => \App\Enums\Role::User, 'guard_name' => $forGuard]);
    }
}
