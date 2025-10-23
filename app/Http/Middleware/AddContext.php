<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Log\Context\Repository;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AddContext
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Core request context
        Context::add([
            'trace_id' => Str::uuid()->toString(),
            'from_path' => $request->path()
        ]);

        // User context (when authenticated)
        Context::when(auth()->check(), static fn (Repository $context) => $context->add([
            'user_id' => auth()->id(),
        ]));

        return $next($request);
    }
}
