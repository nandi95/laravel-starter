<?php

declare(strict_types=1);

Route::get('csrf-cookie', function () {
    return response()->json(['message' => 'CSRF cookie set']);
})->name('csrf-cookie');
