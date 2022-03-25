<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\TestCase;

class LogoutControllerTest extends TestCase
{
    /** @test */
    public function itShouldLogTheUserOutOfTheApplication(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne(['password' => Hash::make('password')]);

        $this->assertGuest();
        $this->postJson(route('api.auth.login'), ['email' => $user->email, 'password' => 'password']);
        $this->assertAuthenticatedAs($user);
        $cookie = $this->postJson(route('api.auth.logout'))->assertNoContent()->getCookie('XSRF-TOKEN');
        $this->assertSame(time() - 1, $cookie->getExpiresTime(), 'Cookie token has not been unset');
        $this->assertGuest('web');
    }
}
