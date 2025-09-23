<?php

declare(strict_types=1);

namespace App\Http\Controllers\Authentication;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Traits\ParsesDevice;
use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\CurrentDeviceLogout;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

class AuthController extends Controller
{
    use ParsesDevice;

    /**
     * Register new user
     */
    public function register(Request $request): JsonResponse
    {
        Log::info('Starting new user registration', [
            'email' => $request->get('email'),
            'first_name' => $request->get('first_name'),
            'last_name' => $request->get('last_name')
        ]);

        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'newsletter' => 'boolean',
        ]);

        /** @var User $user */
        $user = User::create([
            'first_name' => $request->get('first_name'),
            'last_name' => $request->get('last_name'),
            'email' => $request->get('email'),
            'password' => Hash::make($request->get('password')),
            'ulid' => Str::ulid()->toBase32(),
        ]);

        $user->assignRole(Role::User);

        event(new Registered($user));

        Log::info('User registered successfully', [
            'user_id' => $user->getKey(),
            'email' => $user->email
        ]);

        return response()->json(status: Response::HTTP_CREATED);
    }

    /**
     * Authenticate user with session-based or API token authentication
     *
     * @throws ValidationException
     */
    public function login(LoginRequest $request): JsonResponse
    {
        Log::info('Login attempt', [
            'email' => $request->get('email'),
            'device' => $this->getDeviceName(),
            'remember' => $request->boolean('remember'),
            'token_requested' => $request->boolean('token')
        ]);

        /** @var User $user */
        $user = User::withTrashed()->where('email', $request->get('email'))->first();

        event(new Attempting('auth:sanctum', $request->only(['email', 'password']), $request->boolean('remember')));
        $request->authenticate($user);

        if ($user->trashed()) {
            $user->restore();
            Log::info('Restored previously soft-deleted user during login', [
                'user_id' => $user->getKey(),
                'email' => $user->email
            ]);
        }

        // Check if client wants API token (mobile/third-party) or session (SPA)
        if (!EnsureFrontendRequestsAreStateful::fromFrontend($request)) {
            $sanctumToken = $user->createToken(
                $this->getDeviceName(),
                ['*'],
                $request->boolean('remember') ?
                    now()->addMonth() :
                    now()->addDay()
            );

            event(new Authenticated('auth:sanctum', $user));
            Log::info('User logged in successfully with API token', [
                'user_id' => $user->getKey(),
                'email' => $user->email,
                'token_expiry' => $request->boolean('remember') ? 'one month' : 'one day'
            ]);

            return response()->json(['data' => $sanctumToken->plainTextToken]);
        }

        // Session-based authentication for SPAs
        auth()->login($user, $request->boolean('remember'));

        // Only regenerate session if session is available
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        event(new Authenticated('auth:sanctum', $user));

        Log::info('User logged in successfully with session', [
            'user_id' => $user->getKey(),
            'email' => $user->email,
            'remember' => $request->boolean('remember')
        ]);

        return response()->json(['message' => 'Login successful']);
    }

    /**
     * Logout user (handles both session and token-based authentication)
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        Log::info('User logging out', [
            'user_id' => $user->getKey(),
            'has_token' => $user->currentAccessToken() !== null
        ]);

        // If user is authenticated via token, delete the current token
        if ($user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
            event(new CurrentDeviceLogout('auth:sanctum', $user));
            Log::info('API token revoked');
        } else {
            // Session-based logout
            auth()->logoutCurrentDevice();

            // Only invalidate session if session is available
            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            Log::info('Session invalidated');
        }

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }
}
