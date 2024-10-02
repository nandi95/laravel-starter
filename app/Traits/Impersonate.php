<?php

declare(strict_types=1);

namespace App\Traits;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\PersonalAccessToken;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Trait Impersonate
 *
 * This should only be used on the User model.
 * The Reason for the trait is to keep the User model clean and readable.
 */
trait Impersonate
{
    use ParsesDevice;

    /**
     * Return true or false if the user can impersonate another user.
     */
    public function canImpersonate(): bool
    {
        return $this->hasRole(Role::Admin);
    }

    /**
     * Return true or false if the user can be impersonated.
     */
    public function canBeImpersonated(): bool
    {
        return !$this->hasRole(Role::Admin);
    }

    /**
     * Impersonate the given user and return impersonation pat token.
     *
     * @throws InvalidArgumentException
     */
    public function impersonate(User $user): ?string
    {
        if (!$this->canImpersonate() || !$user->canBeImpersonated()) {
            return null;
        }

        /** @var PersonalAccessToken $currentToken */
        if ($currentToken = $this->currentAccessToken()) {
            // this can only happen if the current user isn't signed in with a token
            cache()->set(
                'impersonation_by_' . $this->getKey(),
                $currentToken->only(['expires_at', 'abilities', 'name']),
                now()->addDay()
            );
            $currentToken->delete();
        }

        $newToken = $user->createToken(
            'impersonate_by_' . $this->getKey(),
            $currentToken->getAttribute('abilities') ?? ['*'],
            now()->addDay()
        );
        $accessToken = $newToken->accessToken->forceFill(['impersonator_id' => $this->getKey()]);
        $accessToken->save();

        $user->withAccessToken($accessToken);
        auth()->setUser($user);

        return $newToken->plainTextToken;
    }

    /**
     * Check if the current user is impersonated.
     */
    public function isImpersonated(): bool
    {
        /** @var PersonalAccessToken|null $token */
        $token = $this->currentAccessToken();

        return $token && !is_null($token->getAttribute('impersonator_id'));
    }

    /**
     * Leave the current impersonation and return new pat token.
     */
    public function leaveImpersonation(): ?string
    {
        if (!$this->isImpersonated()) {
            return null;
        }

        /** @var PersonalAccessToken $token */
        $token = $this->currentAccessToken();
        $impersonator = User::findOrFail($token->getAttribute('impersonator_id'));
        $attributes = cache()->pull('impersonation_by_' . $this->getKey(), fn (): array => [
            'expires_at' => now()->addDay(),
            'abilities' => ['*'],
            'name' => $this->getDeviceName()
        ]);

        $newToken = $impersonator->createToken(
            $attributes['name'],
            $attributes['abilities'],
            Carbon::parse($attributes['expires_at'])
        );

        $impersonator->withAccessToken($newToken->accessToken);
        auth()->setUser($impersonator);

        return $newToken->plainTextToken;
    }
}
