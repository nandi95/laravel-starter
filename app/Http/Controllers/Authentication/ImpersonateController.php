<?php

declare(strict_types=1);

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
        $impersonator = Auth::user();

        Log::info('User impersonation attempt', [
            'impersonator_id' => $impersonator->getKey(),
            'target_user_id' => $user->getKey()
        ]);

        $newToken = $impersonator->impersonate($user);

        if (!$newToken) {
            Log::warning('Impersonation failed - insufficient permissions', [
                'impersonator_id' => $impersonator->getKey(),
                'target_user_id' => $user->getKey()
            ]);
            abort(Response::HTTP_FORBIDDEN, 'User cannot be impersonated.');
        }

        Log::info('User impersonation successful', [
            'impersonator_id' => $impersonator->getKey(),
            'target_user_id' => $user->getKey()
        ]);

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

        if (!$newToken) {
            Log::warning('Failed to stop impersonation - user not being impersonated', [
                'user_id' => $impersonated->getKey()
            ]);
            abort(Response::HTTP_FORBIDDEN, 'User is not impersonated.');
        }

        Log::info('Impersonation stopped successfully', [
            'previously_impersonated_id' => $impersonated->getKey()
        ]);

        return response()->json(['data' => $newToken]);
    }
}
