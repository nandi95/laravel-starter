<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class RecaptchaIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     *
     * @throws ConnectionException
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!app()->isProduction()) {
            return $next($request);
        }

        $value = $request->string('challenge');

        if ($value->isEmpty()) {
            abort(Response::HTTP_FORBIDDEN, 'Recaptcha challenge is empty');
        }

        $recaptchaSecret = config('services.recaptcha.secret');

        if (is_null($recaptchaSecret)) {
            report('Recaptcha secret is not set!');

            return $next($request);
        }

        /**
         * @link https://developers.google.com/recaptcha/docs/v3#site_verify_response
         */
        $response = Http::asForm()
            ->post(
                'https://www.google.com/recaptcha/api/siteverify',
                [
                    'secret' => $recaptchaSecret,
                    'response' => $value->toString(),
                    'remoteip' => $request->ip(),
                ]
            );

        if (
            !$response->successful()
            || !$response->json('success')
            || $response->json('score', 0) < 0.8
            || count($errorCodes = $response->json('error-codes', []))
        ) {
            $error = 'Google recaptcha validation failed';

            if (isset($errorCodes) && count($errorCodes)) {
                $error .= ' with error codes: ' . implode(', ', $errorCodes);
            }

            report($error);

            abort(Response::HTTP_FORBIDDEN, $error);
        }

        return $next($request);
    }
}
