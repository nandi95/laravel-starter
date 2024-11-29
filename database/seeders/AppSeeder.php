<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AppSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::query()->firstOrCreate(
            ['email' => 'support@laravel.dev'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'ulid' => Str::ulid()->toBase32(),
                'email_verified_at' => now(),
            ]);

        if ($admin->wasRecentlyCreated) {
            $admin->assignRole(Role::Admin);
        }

        $testUser = User::query()->firstOrCreate(
            ['email' => 'testing@laravel.dev'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'ulid' => Str::ulid()->toBase32(),
                'email_verified_at' => now(),
            ]);

        if ($testUser->wasRecentlyCreated) {
            $testUser->assignRole(Role::User);
        }
    }
}
