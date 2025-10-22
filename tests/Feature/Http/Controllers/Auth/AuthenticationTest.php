<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Str;

test('users can authenticate using session based login', function (): void {
    $user = User::factory()->create();

    $this->withHeader('Origin', 'localhost')
        ->postJson(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])
        ->assertOk()
        ->assertJsonStructure(['message']);
});
test('users can authenticate using token based login', function (): void {
    $user = User::factory()->create();

    $this->postJson(route('login'), [
        'email' => $user->email,
        'password' => 'password'
    ])
        ->assertOk()
        ->assertJsonStructure(['data']);
});
test('users can not authenticate with invalid password', function (): void {
    $user = User::factory()->create();

    $this->postJson(route('login'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])
        ->assertUnprocessable();
});
test('users can logout', function (): void {
    $user = User::factory()->create([
        'ulid' => Str::ulid()->toBase32(),
    ]);

    $token = $user->createToken('test-token')->plainTextToken;

    $this->postJson(route('logout'), [], [
        'Authorization' => 'Bearer ' . $token,
    ])
        ->assertNoContent();
});
