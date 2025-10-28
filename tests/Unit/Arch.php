<?php

declare(strict_types=1);

arch()->expect('Database\Factories')
    ->toHaveSuffix('Factory');
arch()->expect('Database\Seeders')
    ->toHaveMethod('run')
    ->toHaveSuffix('Seeder');
arch()->preset()->laravel()->ignoring([
    \App\Models\Model::class,
    \App\Http\Controllers\Authentication\EmailVerificationController::class
]);
arch()->preset()->security()->ignoring(['md5']);
arch()->preset()->php();
