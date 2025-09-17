<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    public function test_reset_password_link_can_be_requested(): void
    {
        $user = User::factory()->create();

        $this->postJson(route('password.email'), ['email' => $user->email])
            ->assertOk()
            ->assertExactJson([
                'message' => __('We have emailed your password reset link.'),
            ]);
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $this->withoutExceptionHandling();
        Notification::fake();

        $user = User::factory()->create();

        $this->postJson(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (object $notification) use ($user): bool {
            $this->postJson(route('password.store'), [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
                ->assertExactJson([
                    'message' => 'Your password has been reset.'
                ])
                ->assertJsonMissingPath('errors');

            return true;
        });
    }
}
