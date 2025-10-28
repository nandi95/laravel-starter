<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

use function Pest\Laravel\getJson;
use function Pest\Laravel\withoutExceptionHandling;

test('email can be verified', function (): void {
    withoutExceptionHandling();

    /** @var User $user */
    $user = User::factory()->create(['email_verified_at' => null]);

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'email.verify',
        now()->addMinutes(60),
        ['user' => $user->ulid],
        false
    );

    getJson($verificationUrl)
        ->assertExactJson(['message' => 'Email verified!']);

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});
test('email is not verified with invalid hash', function (): void {
    $user = User::factory()->create(['email_verified_at' => null]);

    $verificationUrl = URL::temporarySignedRoute(
        'email.verify',
        now()->addMinutes(60),
        ['user' => $user->ulid, 'hash' => sha1('wrong-email')]
    );

    getJson($verificationUrl);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});
