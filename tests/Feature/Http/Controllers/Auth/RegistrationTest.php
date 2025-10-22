<?php

declare(strict_types=1);

use Spatie\Permission\Models\Role;

test('new users can register', function (): void {
    $this->withoutExceptionHandling();
    $role = Role::create(['name' => 'user']);

    $this->postJson(route('register'), [
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])
        ->assertCreated();

    $role->delete();
});
