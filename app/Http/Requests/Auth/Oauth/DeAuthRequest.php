<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth\Oauth;

use App\Enums\OauthProvider;
use App\Http\Requests\FormRequest;
use JsonException;

/**
 * @link https://developers.facebook.com/docs/development/create-an-app/app-dashboard/data-deletion-callback/#implementing
 */
class DeAuthRequest extends FormRequest
{
    /**
     * @throws JsonException
     */
    #[\Override]
    public function authorize(): bool
    {
        if ($this->route('provider') !== OauthProvider::FACEBOOK->value) {
            // not implemented for other providers
            return false;
        }

        $payload = $this->string('signed_request')->explode('.', 2)->first();

        // confirm the signature
        return $this->decodedSignature()['signature'] === hash_hmac('sha256', (string) $payload, (string) config('services.meta.app_secret'), true);
    }

    /**
     * @return array{
     *     signature: string,
     *     user: array{
     *         algorithm: string,
     *         expires: int,
     *         issued_at: int,
     *         user_id: string
     *     }
     * }
     *
     * @throws JsonException
     */
    public function decodedSignature(): array
    {
        $encodedSignature = $this->string('signed_request')->explode('.', 2)->first();
        $payload = $this->string('signed_request')->explode('.', 2)->last();

        return [
            'signature' => base64_decode(strtr($encodedSignature, '-_', '+/'), true),
            'user' => json_decode(base64_decode(strtr($payload, '-_', '+/'), true), true, 512, JSON_THROW_ON_ERROR)
        ];
    }
}
