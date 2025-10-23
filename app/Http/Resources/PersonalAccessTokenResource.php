<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * @property PersonalAccessToken $resource
 */
class PersonalAccessTokenResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'device' => $this->whenHas('device'),
            'browser' => $this->whenHas('browser'),
            'location' => $this->whenHas('location'),
            'lastUsedAt' => $this->resource->last_used_at,
            'isCurrent' => $this->whenHas('is_current'),
            'hash' => $this->whenHas('hash')
        ];
    }
}
