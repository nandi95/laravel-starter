<?php

declare(strict_types=1);

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class EmailVerificationController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function verifyEmail(Request $request, User $user): JsonResponse
    {
        Log::info('Email verification attempt', [
            'user_id' => $user->getKey(),
            'email' => $user->email
        ]);

        if (!URL::hasCorrectSignature($request, false)) {
            Log::warning('Invalid signature for email verification', [
                'user_id' => $user->getKey(),
                'email' => $user->email
            ]);
            throw new InvalidSignatureException;
        }

        if (!URL::signatureHasNotExpired($request)) {
            Log::info('Email verification link expired, sending new link', [
                'user_id' => $user->getKey(),
                'email' => $user->email
            ]);
            $user->sendEmailVerificationNotification();
            abort(403, __('The verification link has expired. New link sent!'));
        }

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            Log::info('Email verified successfully', [
                'user_id' => $user->getKey(),
                'email' => $user->email
            ]);
            event(new Verified($user));
        } else {
            Log::debug('Email already verified', [
                'user_id' => $user->getKey(),
                'email' => $user->email
            ]);
        }

        return response()->json(['message' => 'Email verified!']);
    }

    /**
     * Send a new email verification notification.
     */
    public function verificationNotification(Request $request): JsonResponse
    {
        Log::info('Email verification notification requested', [
            'email' => $request->get('email')
        ]);

        $request->validate([
            'email' => ['required', 'email'],
        ]);

        /** @var User $user */
        $user = User::where('email', $request->get('email'))->whereNull('email_verified_at')->first();

        if (!$user) {
            Log::warning('Email verification requested for non-existent or already verified user', [
                'email' => $request->get('email')
            ]);
            abort(Response::HTTP_BAD_REQUEST);
        }

        Log::info('Sending verification notification', [
            'user_id' => $user->getKey(),
            'email' => $user->email
        ]);

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => __('Verification link sent!')]);
    }
}
