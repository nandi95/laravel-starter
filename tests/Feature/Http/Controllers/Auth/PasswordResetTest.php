<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

test('reset password link can be requested', function (): void {
    $user = User::factory()->create();

    $this->postJson(route('password.email'), ['email' => $user->email])
        ->assertOk()
        ->assertExactJson([
            'message' => __('We have emailed your password reset link.'),
        ]);
});
test('password can be reset with valid token', function (): void {
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
});
