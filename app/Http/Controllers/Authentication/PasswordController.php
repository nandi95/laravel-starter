<?php

declare(strict_types=1);

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class PasswordController extends Controller
{
    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function sendResetPasswordLink(Request $request): JsonResponse
    {
        Log::info('Password reset link requested', [
            'email' => $request->get('email')
        ]);

        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status !== Password::RESET_LINK_SENT) {
            Log::warning('Failed to send password reset link', [
                'email' => $request->get('email'),
                'status' => $status
            ]);
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        Log::info('Password reset link sent successfully', [
            'email' => $request->get('email')
        ]);

        return response()->json(['message' => __($status)]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws ValidationException
     */
    public function resetPassword(Request $request): JsonResponse
    {
        Log::info('Password reset attempt', [
            'email' => $request->get('email')
        ]);

        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email', Rule::exists(User::class, 'email')],
            // todo - add check so it's not the same as the old password
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $request->only('email', 'password', 'token'),
            static function (User $user, string $password): void {
                $user->forceFill(['password' => Hash::make($password)])->save();
                Log::info('Password reset successful', [
                    'user_id' => $user->getKey(),
                    'email' => $user->email
                ]);
                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            Log::warning('Password reset failed', [
                'email' => $request->get('email'),
                'status' => $status
            ]);
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json(['message' => __($status)]);
    }

    /**
     * Update the user's password.
     *
     * @throws ValidationException
     */
    public function password(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        Log::info('Password update attempt', [
            'user_id' => $user->getKey()
        ]);

        $request->validate([
            'current_password' => [$user->password ? 'required' : 'sometimes', 'string'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        if ($user->password && $request->has('current_password') && !Hash::check($request->get('current_password'), $user->password)) {
            Log::warning('Invalid current password provided for password update', [
                'user_id' => $user->getKey()
            ]);
            throw ValidationException::withMessages([
                'current_password' => __('auth.password'),
            ]);
        }

        $user->update([
            'password' => Hash::make($request->get('password')),
        ]);

        Log::info('Password updated successfully', [
            'user_id' => $user->getKey()
        ]);

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }
}
