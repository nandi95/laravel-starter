<?php

declare(strict_types=1);

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;
use App\Http\Resources\PersonalAccessTokenResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class DeviceController extends Controller
{
    /**
     * Get authenticated user devices
     */
    public function devices(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();
        /** @var PersonalAccessToken $currentToken */
        $currentToken = $user->currentAccessToken();

        Log::debug('Fetching user devices', [
            'user_id' => $user->getKey(),
            'current_token_id' => $currentToken->getKey()
        ]);

        $tokens = $user->tokens()
            ->orderByDesc('last_used_at')
            ->whereNull('impersonator_id')
            ->get()
            ->map(fn (PersonalAccessToken $token) => $token->forceFill([
                'device' => Str::before($token->name, ' / '),
                'browser' => Str::after($token->name, ' / '),
                'hash' => Crypt::encryptString($token->getKey()),
                'is_current' => $currentToken->getKey() === $token->getKey(),
            ]));

        Log::debug('Retrieved user devices', [
            'user_id' => $user->getKey(),
            'device_count' => $tokens->count()
        ]);

        return PersonalAccessTokenResource::collection($tokens);
    }

    /**
     * Revoke token by id
     */
    public function deviceDisconnect(Request $request): JsonResponse
    {
        Log::info('Device disconnect request received', [
            'user_id' => $request->user()->getKey()
        ]);

        $request->validate([
            'hash' => 'required',
        ]);

        /** @var User $user */
        $user = $request->user();

        $id = (int) Crypt::decryptString($request->get('hash'));

        /** @var PersonalAccessToken $currentToken */
        $currentToken = $user->currentAccessToken();

        if ($currentToken->getKey() === $id) {
            Log::warning('Attempted to disconnect current device', [
                'user_id' => $user->getKey(),
                'token_id' => $id
            ]);

            return response()->json([
                'message' => __('You cannot disconnect the current device.'),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($id !== 0) {
            Log::info('Disconnecting device', [
                'user_id' => $user->getKey(),
                'token_id' => $id
            ]);
            $user->tokens()
                ->whereKey($id)
                ->delete();
        }

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }
}
