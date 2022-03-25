<?php

namespace Database\Seeders;

use App\Enums\Unit;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     * @throws \Throwable
     */
    public function run(): void
    {
        DB::transaction(static function () {
            User::firstOrCreate(
                ['email' => 'test@email.com'],
                User::factory()->raw(['password' => Hash::make('password'), 'email' => 'test@email.com'])
            );
        });
    }
}
