<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\User;

beforeEach(function (): void {
    $this->admin = User::factory()->create();
    $this->setupRoles();
    $this->admin->assignRole(Role::Admin);
    $this->regularUser = User::factory()->create(['first_name' => 'Regular', 'last_name' => 'User']);
});
test('admin can list users', function (): void {
    // Create some regular users
    User::factory()->count(3)->create();

    $this->actingAs($this->admin)
        ->getJson(route('users'))
        ->assertOk()
        ->assertJsonCount(3 + 1, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'avatar'
                ]
            ],
            'meta',
            'links'
        ]);
});
test('admin cannot see other admins in list', function (): void {
    // Create another admin
    $otherAdmin = User::factory()->create();
    $otherAdmin->assignRole(Role::Admin);

    // Create some regular users
    User::factory()->count(3)->create();

    $response = $this->actingAs($this->admin)
        ->getJson(route('users'))
        ->assertOk();

    $userIds = collect($response->json('data'))->pluck('id')->toArray();

    expect($userIds)->not->toContain($otherAdmin->getKey());
    expect($userIds)->toHaveCount(3 + 1);
});
test('users are ordered by name', function (): void {
    /* @var User $userA */
    $userC = User::factory()->create(['first_name' => 'Charlie', 'last_name' => '']);

    /* @var User $userB */
    $userA = User::factory()->create(['first_name' => 'Alice', 'last_name' => '']);

    /* @var User $userB */
    $userB = User::factory()->create(['first_name' => 'Bob', 'last_name' => '']);

    $userIds = $this->actingAs($this->admin)
        ->getJson(route('users'))
        ->assertOk()
        ->json('data.*.id');

    expect($userIds)->toEqual([$userA->ulid, $userB->ulid, $userC->ulid, $this->regularUser->ulid]);
});
test('regular users cannot list users', function (): void {
    $this->actingAs($this->regularUser)
        ->getJson(route('users'))
        ->assertForbidden();
});
test('unauthenticated users cannot list users', function (): void {
    $this->getJson(route('users'))
        ->assertUnauthorized();
});
test('pagination works correctly', function (): void {
    // Create 15 users
    User::factory()->count(15)->create();

    $response = $this->actingAs($this->admin)
        ->getJson(route('users'))
        ->assertOk();

    expect($response->json('data'))->toHaveCount(10);
    expect($response->json())->toHaveKey('meta');
    expect($response->json())->toHaveKey('links');
});
