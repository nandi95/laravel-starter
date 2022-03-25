<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\UserController;
use Illuminate\Routing\Router;

/* @var Router $router */
$router->post('login', LoginController::class)->name('auth.login');

$router->group(['middleware' => 'auth:sanctum'], static function (Router $router) {
    $router->post('logout', LogoutController::class)->name('auth.logout');

    $router->get('users', [UserController::class, 'show'])
        ->name('users.show')
        // cache for 30 days
        ->middleware('cache.headers:private;max_age=2592000;etag;must_revalidate;');
});
