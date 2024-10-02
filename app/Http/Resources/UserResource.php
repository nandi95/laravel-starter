<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @property User resource
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function toArray(Request $request): array
    {
        $avatar = 'https://www.gravatar.com/avatar/' . md5($this->resource->email);

        if ($this->resource->avatar) {
            // avatar might be coming from a user provider
            $avatar = Str::contains($this->resource->avatar, 'https://')
                ? $this->resource->avatar
                // add cdn here
                : Storage::url($this->resource->avatar);
        }

        return [
            'id' => $this->resource->ulid,
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'hasPassword' => (bool) $this->resource->password,
            'avatar' => $avatar,
            'emailVerified' => $this->resource->hasVerifiedEmail(),
            'roles' => $this->when(
                $this->resource->relationLoaded('roles'),
                fn () => $this->resource->roles->pluck('name')->toArray()
            ),
            'userProviders' => $this->when(
                $this->resource->relationLoaded('userProviders'),
                fn () => $this->resource->userProviders->pluck('name')->toArray()
            ),
            'unreadNotificationsCount' => $this->whenCounted('unreadNotifications')
        ];
    }
}
