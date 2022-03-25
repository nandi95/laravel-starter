<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\TestCase;

class LoginControllerTest extends TestCase
{
    /** @test */
    public function itShouldSuccessfullyLogIn(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne(['password' => Hash::make('password')]);

        $this->assertGuest();
        $this->postJson(route('api.auth.login'), ['email' => $user->email, 'password' => 'password'])
            ->assertNoContent()
            ->assertCookie(config('session.cookie'))
            ->assertCookie('XSRF-TOKEN');
        $this->assertAuthenticatedAs($user);
    }
}
