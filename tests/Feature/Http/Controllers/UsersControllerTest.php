<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Enums\Role;
use App\Models\User;
use Tests\TestCase;

class UsersControllerTest extends TestCase
{
    private User $admin;

    private User $regularUser;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        $this->setupRoles();
        $this->admin->assignRole(Role::Admin);
        $this->regularUser = User::factory()->create(['first_name' => 'Regular', 'last_name' => 'User']);
    }

    public function test_admin_can_list_users(): void
    {
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
    }

    public function test_admin_cannot_see_other_admins_in_list(): void
    {
        // Create another admin
        $otherAdmin = User::factory()->create();
        $otherAdmin->assignRole(Role::Admin);

        // Create some regular users
        User::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->getJson(route('users'))
            ->assertOk();

        $userIds = collect($response->json('data'))->pluck('id')->toArray();

        $this->assertNotContains($otherAdmin->getKey(), $userIds);
        $this->assertCount(3 + 1, $userIds);
    }

    public function test_users_are_ordered_by_name(): void
    {
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

        $this->assertEquals([$userA->ulid, $userB->ulid, $userC->ulid, $this->regularUser->ulid], $userIds);
    }

    public function test_regular_users_cannot_list_users(): void
    {
        $this->actingAs($this->regularUser)
            ->getJson(route('users'))
            ->assertForbidden();
    }

    public function test_unauthenticated_users_cannot_list_users(): void
    {
        $this->getJson(route('users'))
            ->assertUnauthorized();
    }

    public function test_pagination_works_correctly(): void
    {
        // Create 15 users
        User::factory()->count(15)->create();

        $response = $this->actingAs($this->admin)
            ->getJson(route('users'))
            ->assertOk();

        $this->assertCount(10, $response->json('data'));
        $this->assertArrayHasKey('meta', $response->json());
        $this->assertArrayHasKey('links', $response->json());
    }
}
