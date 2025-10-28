<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OauthProvider;
use App\Models\User;
use App\Models\UserProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<UserProvider>
 */
class UserProviderFactory extends Factory
{
    protected $model = UserProvider::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function definition(): array
    {
        return [
            'provider_id' => Str::random(),
            'user_id' => User::factory(...),
            'name' => fake()->randomElement(OauthProvider::cases()),
        ];
    }
}
