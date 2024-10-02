<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use App\Enums\OauthProvider;
use App\Models\User;
use App\Models\UserProvider;
use Illuminate\Encryption\Encrypter;
use Tests\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(\App\Http\Controllers\Authentication\OAuthController::class)]
class OAuthControllerTest extends TestCase
{
    public function testDeletionStatusForExistingUser(): void
    {
        /** @var User $user */
        $user = User::factory()
            ->has(UserProvider::factory())
            ->createOne();

        $payload = encrypt([
            'id' => $user->ulid,
            'provider' => $user->userProviders()->first()->name
        ]);

        $this->getJson(route('oauth.deletion-status', ['code' => $payload]))
            ->assertOk()
            ->assertJson(['data' => [
                'userExists' => true,
                'toBeDeletedAt' => null
            ]]);
    }

    public function testDeletionStatusForNonExistingUser(): void
    {
        $payload = encrypt([
            'id' => 'random',
            'provider' => OauthProvider::GOOGLE
        ]);

        $this->getJson(route('oauth.deletion-status', ['code' => $payload]))
            ->assertOk()
            ->assertJson(['data' => [
                'userExists' => false,
                'toBeDeletedAt' => null
            ]]);
    }

    public function testDeletionStatusWithIncorrectEncryptionKey(): void
    {
        $encrypter = new Encrypter(Encrypter::generateKey(config('app.cipher')), config('app.cipher'));

        $payload = $encrypter->encrypt([
            'id' => 'random',
            'provider' => OauthProvider::GOOGLE
        ]);

        $this->getJson(route('oauth.deletion-status', ['code' => $payload]))
            ->assertBadRequest();
    }

    public function testDeletionStatusForSoftDeletedUser(): void
    {
        $this->withoutExceptionHandling();
        $now = now()->toImmutable();

        /** @var User $user */
        $user = User::factory()
            ->has(UserProvider::factory())
            ->createOne(['deleted_at' => $now]);

        $payload = encrypt([
            'id' => $user->ulid,
            'provider' => $user->userProviders()->first()->name
        ]);

        $this->getJson(route('oauth.deletion-status', ['code' => $payload]))
            ->assertOk()
            ->assertJson(['data' => [
                'userExists' => true,
                'toBeDeletedAt' => $now->addDays(30)->toDateString()
            ]]);

        $user->deleted_at = $now->subDays(10);
        $user->save();

        $this->getJson(route('oauth.deletion-status', ['code' => $payload]))
            ->assertOk()
            ->assertJson(['data' => [
                'userExists' => true,
                'toBeDeletedAt' => $now->addDays(20)->toDateString()
            ]]);
    }

    public function testDeletionStatusWithIncorrectDataInPayload(): void
    {
        $payload = encrypt([
            'provider' => OauthProvider::GOOGLE
        ]);

        $this->getJson(route('oauth.deletion-status', ['code' => $payload]))
            ->assertBadRequest();
    }
}
