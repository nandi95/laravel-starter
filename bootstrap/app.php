<?php

declare(strict_types=1);

use App\Http\Middleware\AddContext;
use App\Http\Middleware\JsonResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Spatie\Permission\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        apiPrefix: ''
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware
            ->statefulApi()
            ->throttleApi(redis: true)
            ->trustProxies(at: [
                '127.0.0.1',
            ])
            ->api(prepend: [
                JsonResponse::class,
            ])
            ->alias([
                'role' => RoleMiddleware::class
            ])
            ->prepend(AddContext::class)
            ->append(SetCacheHeaders::using([
                'etag',
                'max_age' => 0,
                'public',
            ]));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
