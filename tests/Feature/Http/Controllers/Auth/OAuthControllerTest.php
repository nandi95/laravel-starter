<?php

declare(strict_types=1);

use App\Enums\OauthProvider;
use App\Models\User;
use App\Models\UserProvider;
use Illuminate\Encryption\Encrypter;

use function Pest\Laravel\getJson;
use function Pest\Laravel\withoutExceptionHandling;

test('deletion status for existing user', function (): void {
    /** @var User $user */
    $user = User::factory()
        ->has(UserProvider::factory())
        ->createOne();

    $payload = encrypt([
        'id' => $user->ulid,
        'provider' => $user->userProviders()->first()->name
    ]);

    getJson(route('oauth.deletion-status', ['code' => $payload]))
        ->assertOk()
        ->assertJson(['data' => [
            'userExists' => true,
            'toBeDeletedAt' => null
        ]]);
});
test('deletion status for non existing user', function (): void {
    $payload = encrypt([
        'id' => 'random',
        'provider' => OauthProvider::GOOGLE
    ]);

    getJson(route('oauth.deletion-status', ['code' => $payload]))
        ->assertOk()
        ->assertJson(['data' => [
            'userExists' => false,
            'toBeDeletedAt' => null
        ]]);
});
test('deletion status with incorrect encryption key', function (): void {
    $encrypter = new Encrypter(Encrypter::generateKey(config('app.cipher')), config('app.cipher'));

    $payload = $encrypter->encrypt([
        'id' => 'random',
        'provider' => OauthProvider::GOOGLE
    ]);

    getJson(route('oauth.deletion-status', ['code' => $payload]))
        ->assertBadRequest();
});
test('deletion status for soft deleted user', function (): void {
    withoutExceptionHandling();
    $now = now()->toImmutable();

    /** @var User $user */
    $user = User::factory()
        ->has(UserProvider::factory())
        ->createOne(['deleted_at' => $now]);

    $payload = encrypt([
        'id' => $user->ulid,
        'provider' => $user->userProviders()->first()->name
    ]);

    getJson(route('oauth.deletion-status', ['code' => $payload]))
        ->assertOk()
        ->assertJson(['data' => [
            'userExists' => true,
            'toBeDeletedAt' => $now->addDays(30)->toDateString()
        ]]);

    $user->deleted_at = $now->subDays(10);
    $user->save();

    getJson(route('oauth.deletion-status', ['code' => $payload]))
        ->assertOk()
        ->assertJson(['data' => [
            'userExists' => true,
            'toBeDeletedAt' => $now->addDays(20)->toDateString()
        ]]);
});
test('deletion status with incorrect data in payload', function (): void {
    $payload = encrypt([
        'provider' => OauthProvider::GOOGLE
    ]);

    getJson(route('oauth.deletion-status', ['code' => $payload]))
        ->assertBadRequest();
});
