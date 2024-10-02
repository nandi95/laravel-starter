<?php

declare(strict_types=1);

namespace App\Http\Controllers\Authentication;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Traits\ParsesDevice;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ParsesDevice;

    /**
     * Register new user
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'newsletter' => 'boolean',
        ]);

        /** @var User $user */
        $user = User::create([
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'password' => Hash::make($request->get('password')),
            'ulid' => Str::ulid()->toBase32(),
        ]);

        $user->assignRole(Role::User);

        event(new Registered($user));

        return response()->json(status: Response::HTTP_CREATED);
    }

    /**
     * Generate sanctum token on successful login
     *
     * @throws ValidationException
     */
    public function login(LoginRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = User::withTrashed()->where('email', $request->get('email'))->first();

        $request->authenticate($user);

        if ($user->trashed()) {
            $user->restore();
        }

        $sanctumToken = $user->createToken(
            $this->getDeviceName(),
            ['*'],
            $request->boolean('remember') ?
                now()->addMonth() :
                now()->addDay()
        );

        return response()->json(['data' => $sanctumToken->plainTextToken]);
    }

    /**
     * Revoke token; only remove token that is used to perform logout (i.e. will not revoke all tokens)
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }
}
