<?php

namespace Tests\Feature\Http\Controllers\User;

use App\Models\User;
use Tests\Feature\TestCase;

class UserShowTest extends TestCase
{
    /** @test */
    public function itShouldReturnTheUserThemselves(): void
    {
        User::factory()->count(2)->create();
        /** @var User $user */
        $user = User::factory()->createOne();

        $this->postJson(route('api.auth.login'), ['email' => $user->email, 'password' => 'password']);
        $this->getJson(route('api.users.show'))
            ->assertOk()
            ->assertExactJson(['data' => $user->only('name', 'email', $user->getKeyName())])
            ->assertHeader('cache-control', 'max-age=2592000, must-revalidate, private')
            ->assertHeader('etag');
    }
}
