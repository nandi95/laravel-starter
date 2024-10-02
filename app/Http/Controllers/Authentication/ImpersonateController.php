<?php

declare(strict_types=1);

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Psr\SimpleCache\InvalidArgumentException;

class ImpersonateController extends Controller
{
    /**
     * Impersonate the given user.
     *
     * @throws InvalidArgumentException
     */
    public function impersonate(Request $request, User $user): JsonResponse
    {
        /** @var User $impersonator */
        $impersonator = auth()->user();
        $newToken = $impersonator->impersonate($user);

        abort_unless($newToken, Response::HTTP_FORBIDDEN, 'User cannot be impersonated.');

        return response()->json(['data' => $newToken]);
    }

    /**
     * Stop impersonating the current user.
     */
    public function stopImpersonating(Request $request): JsonResponse
    {
        /** @var User $impersonated */
        $impersonated = auth()->user();
        $newToken = $impersonated->leaveImpersonation();

        abort_unless($newToken, Response::HTTP_FORBIDDEN, 'User is not impersonated.');

        return response()->json(['data' => $newToken]);
    }
}
