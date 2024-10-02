<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Image;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Image>
 */
class ImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function definition(): array
    {
        return [
            'size' => fake()->numberBetween(1024 * 1024, 5 * 1024 * 1024),
            'width' => fake()->numberBetween(640, 1920),
            'height' => fake()->numberBetween(480, 1080),
            'title' => fake()->words(asText: true),
            'mime_type' => 'image/jpeg',
            'storage_location' => 'https://loremflickr.com/640/480?lock=' . fake()->unique()->randomNumber(3)
        ];
    }
}
