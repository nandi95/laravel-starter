<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    public function test_email_can_be_verified(): void
    {
        $this->withoutExceptionHandling();
        /** @var User $user */
        $user = User::factory()->create(['email_verified_at' => null]);

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'email.verify',
            now()->addMinutes(60),
            ['user' => $user->ulid],
            false
        );

        $this->getJson($verificationUrl)
            ->assertExactJson(['message' => 'Email verified!']);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        $verificationUrl = URL::temporarySignedRoute(
            'email.verify',
            now()->addMinutes(60),
            ['user' => $user->ulid, 'hash' => sha1('wrong-email')]
        );

        $this->getJson($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
}
