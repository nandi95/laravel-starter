<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    public function test_new_users_can_register(): void
    {
        $this->withoutExceptionHandling();
        $role = Role::create(['name' => 'user']);

        $this->postJson(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertCreated();

        $role->delete();
    }
}
