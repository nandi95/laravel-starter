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

        return PersonalAccessTokenResource::collection($tokens);
    }

    /**
     * Revoke token by id
     */
    public function deviceDisconnect(Request $request): JsonResponse
    {
        $request->validate([
            'hash' => 'required',
        ]);

        /** @var User $user */
        $user = $request->user();

        $id = (int) Crypt::decryptString($request->get('hash'));

        /** @var PersonalAccessToken $currentToken */
        $currentToken = $user->currentAccessToken();

        if ($currentToken->getKey() === $id) {
            return response()->json([
                'message' => __('You cannot disconnect the current device.'),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($id !== 0) {
            $user->tokens()
                ->whereKey($id)
                ->delete();
        }

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }
}
