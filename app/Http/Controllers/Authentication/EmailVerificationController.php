<?php

declare(strict_types=1);

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Support\Facades\URL;

class EmailVerificationController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function verifyEmail(Request $request, User $user): JsonResponse
    {
        if (!URL::hasCorrectSignature($request, false)) {
            throw new InvalidSignatureException;
        }

        if (!URL::signatureHasNotExpired($request)) {
            $user->sendEmailVerificationNotification();
            abort(403, __('The verification link has expired. New link sent!'));
        }

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();

            event(new Verified($user));
        }

        return response()->json(['message' => 'Email verified!']);
    }

    /**
     * Send a new email verification notification.
     */
    public function verificationNotification(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        /** @var User $user */
        $user = User::where('email', $request->get('email'))->whereNull('email_verified_at')->first();
        abort_unless((bool) $user, 400);

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => __('Verification link sent!')]);
    }
}
