<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::query()->firstOrCreate(['name' => \App\Enums\Role::Admin]);
        Role::query()->firstOrCreate(['name' => \App\Enums\Role::User]);
    }
}
