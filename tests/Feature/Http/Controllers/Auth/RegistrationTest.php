<?php

declare(strict_types=1);

use Spatie\Permission\Models\Role;

use function Pest\Laravel\postJson;
use function Pest\Laravel\withoutExceptionHandling;

test('new users can register', function (): void {
    withoutExceptionHandling();
    $role = Role::create(['name' => 'user']);

    postJson(route('register'), [
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])
        ->assertCreated();

    $role->delete();
});
