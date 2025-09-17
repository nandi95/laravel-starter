<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $this->postJson(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->postJson(route('login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
            ->assertUnprocessable();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create([
            'ulid' => Str::ulid()->toBase32(),
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $this->postJson(route('logout'), [], [
            'Authorization' => 'Bearer ' . $token,
        ])
            ->assertNoContent();
    }
}
