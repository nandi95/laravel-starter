<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Notifications\DatabaseNotification;

class DevelopmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /** @var User $admin */
        $admin = User::query()->where('email', 'support@laravel.dev')->firstOrFail();

        User::factory(12)->createMany()->each->assignRole(Role::User);

        $data = collect()->times(12, fn (): array => [
            'id' => fake()->uuid(),
            // todo - add valid type
            'type' => 'some type',
            'notifiable_id' => $admin->getKey(),
            'notifiable_type' => $admin->getMorphClass(),
            'created_at' => now(),
            'updated_at' => now(),
            'data' => json_encode([
                'title' => 'New contact form submission.',
                'name' => 'John Doe',
                'email' => 'john.doe@email.com',
                'message' => 'Hello, I would like to know more about your services.'
            ], JSON_THROW_ON_ERROR)
        ]);

        DatabaseNotification::query()->insert($data->toArray());
    }
}
