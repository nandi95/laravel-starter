<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Carbon\CarbonImmutable;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[\Override]
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->createRateLimiters();
        $this->createUrls();

        Model::shouldBeStrict(!$this->app->isProduction());

        if (!$this->app->isProduction()) {
            Mail::alwaysTo('support@laravel.dev');
        }

        Relation::enforceMorphMap([
            'user' => User::class
        ]);

        Password::defaults(function () {
            $rule = Password::min(8)->max(255);

            return $this->app->isProduction()
                ? $rule->mixedCase()->uncompromised()->numbers()->symbols()->symbols()->letters()
                : $rule;
        });

        if (class_exists('Dedoc\Scramble\Scramble')) {
            Scramble::configure()
                ->withDocumentTransformers(function (OpenApi $openApi): void {
                    $openApi->secure(
                        SecurityScheme::apiKey('cookie', 'laravel_session')
                            ->setDescription('The session cookie used for authentication. Requests from this page will be treated as authenticated without the cookie.'),
                    );
                })
                ->routes(static fn (Route $route): bool => $route->getName() && !Str::startsWith($route->getName(), ['_boost']));
        }

        Date::use(CarbonImmutable::class);
    }

    private function createRateLimiters(): void
    {
        RateLimiter::for(
            'api',
            static fn (Request $request) => app()->runningUnitTests()
                ? Limit::none()
                : Limit::perMinute(auth()->check() ? 120 : 60)
                    ->by($request->user()?->getKey() ?: $request->ip())
        );

        RateLimiter::for(
            'verification-notification',
            static function (Request $request): array {
                $identifier = $request->user()?->getKey() ?? $request->ip();

                $limitReached = static fn (Request $request, array $headers) => response()->json(
                    ['message' => 'You have sent too many verification emails. Please try again later.'],
                    Response::HTTP_TOO_MANY_REQUESTS,
                    $headers
                );

                return [
                    // 4 requests per hour
                    Limit::perHour(4)->by($identifier . '-hour')->response($limitReached),
                    // and only 1 request per minute
                    Limit::perMinute(1)->by($identifier . '-minute')->response($limitReached),
                ];
            }
        );
    }

    private function createUrls(): void
    {
        ResetPassword::createUrlUsing(
            static fn (object $notifiable, string $token): string => config('app.frontend_url') . "/auth/reset/{$token}?email={$notifiable->getEmailForPasswordReset()}"
        );

        VerifyEmail::createUrlUsing(static function (object $notifiable): string {
            $url = url()->temporarySignedRoute(
                'email.verify',
                now()->addMinutes(config('auth.verification.expire', 60)),
                [
                    'user' => $notifiable->ulid,
                ],
                false
            );

            return config('app.frontend_url') . '/auth/verify?verify_url=' . urlencode($url);
        });
    }
}
