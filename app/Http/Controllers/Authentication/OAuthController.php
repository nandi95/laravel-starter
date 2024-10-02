<?php

declare(strict_types=1);

namespace App\Http\Controllers\Authentication;

use App\Enums\OauthProvider;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\Oauth\DeAuthRequest;
use App\Models\User;
use App\Models\UserProvider;
use App\Traits\ParsesDevice;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use JsonException;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class OAuthController extends Controller
{
    use ParsesDevice;

    /**
     * Redirect to provider for authentication
     */
    public function redirect(Request $request, OauthProvider $provider)
    {
        return Socialite::driver($provider->value)
            ->stateless()
            ->redirect();
    }

    /**
     * Handle callback from provider
     *
     * @throws Throwable
     */
    public function callback(Request $request, OauthProvider $provider): View
    {
        /** @var \Laravel\Socialite\Two\User $oAuthUser */
        $oAuthUser = Socialite::driver($provider->value)
            ->stateless()
            ->user();

        if (!$oAuthUser?->token) {
            return view('oauth', [
                'message' => [
                    'ok' => false,
                    'provider' => $provider,
                    'message' => __('Unable to authenticate with :provider', ['provider' => $provider->value]),
                ],
            ]);
        }

        /** @var UserProvider|null $userProvider */
        $userProvider = UserProvider::select(['id', 'user_id'])
            ->where('name', $provider)
            ->where('provider_id', $oAuthUser->id)
            ->first();

        /** @var User $user */
        if (!$userProvider) {
            if (User::where('email', $oAuthUser->getEmail())->exists()) {
                return view('oauth', [
                    'message' => [
                        'ok' => false,
                        'message' => __('Unable to authenticate with :provider. User with email :email already exists. To connect a new service to your settings, you can go to your settings settings and go through the process of linking your settings.', [
                            'provider' => $provider->value,
                            'email' => $oAuthUser->email,
                        ]),
                    ],
                ]);
            }

            $user = DB::transaction(static function () use ($oAuthUser, $provider, &$user) {
                $user = User::create([
                    'avatar' => $oAuthUser->getAvatar(),
                    'name' => $oAuthUser->getName(),
                    'email' => $oAuthUser->getEmail(),
                    'email_verified_at' => now(),
                    'ulid' => Str::ulid()->toBase32()
                ]);

                $user->assignRole(Role::User);

                $user->userProviders()->create([
                    'provider_id' => $oAuthUser->getId(),
                    'name' => $provider,
                ]);

                return $user;
            });
        } else {
            $user = $userProvider->user;
        }

        if ($user->trashed()) {
            // Was staged for deletion, but the user has returned
            $user->restore();
        }

        $sanctumToken = $user->createToken(
            $this->getDeviceName(),
            ['*'],
            now()->addMonth()
        );

        $sanctumToken->accessToken->save();

        return view('oauth', [
            'message' => [
                'ok' => true,
                'provider' => $provider->value,
                'token' => $sanctumToken->plainTextToken,
            ],
        ]);
    }

    /**
     * De-authenticate user from provider.
     *
     * https://developers.facebook.com/docs/development/create-an-app/app-dashboard/data-deletion-callback/
     *
     * @throws JsonException
     */
    public function deAuth(DeAuthRequest $request, OauthProvider $provider): JsonResponse
    {
        /** @var UserProvider $userProvider */
        $userProvider = UserProvider::whereName($provider)
            ->where('provider_id', $request->decodedSignature()['user']['user_id'])
            ->with('user')
            ->firstOrFail();

        // if the user has normal login or other social logins
        if ($userProvider->user->password || $userProvider->user->userProviders()->count() > 1) {
            // delete this social login only
            $userProvider->delete();
        } else {
            // Stage user for deletion
            $userProvider->user->delete();
        }

        return response()->json([
            'url' => config('app.frontend_url') . '/auth/deletion-status',
            'confirmation_code' => encrypt([
                'id' => $userProvider->user->ulid,
                'provider' => $provider,
            ])
        ]);
    }

    public function deletionStatus(Request $request): JsonResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        $payload = rescue(
            static fn () => decrypt($request->input('code')),
            static fn () => abort(Response::HTTP_BAD_REQUEST, 'Invalid code.'),
            static fn (Throwable $throwable): bool => !$throwable instanceof DecryptException
        );

        abort_unless(isset($payload['id'], $payload['provider']), Response::HTTP_BAD_REQUEST, 'Invalid code.');

        /** @var User|null $user */
        $user = User::withTrashed()
            ->where('ulid', $payload['id'])
            ->whereHas(
                'userProviders',
                fn (Builder|EloquentBuilder $query) => $query->where('name', $payload['provider'])
            )
            ->first();

        return response()->json(['data' => [
            'userExists' => (bool) $user,
            'toBeDeletedAt' => $user?->deleted_at?->addDays(User::DELETION_DELAY)->toDateString(),
        ]]);
    }
}
