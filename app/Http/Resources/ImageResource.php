<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\Role;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Image $resource
 */
class ImageResource extends JsonResource
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
            'id' => $this->when(auth()->user()?->hasRole(Role::Admin), $this->resource->getKey()),
            'width' => $this->resource->width,
            'height' => $this->resource->height,
            'title' => $this->resource->title,
            'source' => $this->resource->source,
            'meta' => ImageableResource::make($this->whenPivotLoaded('imageables', fn () => $this->resource->pivot)),
            'usedTimes' => $this->when($this->resource->hasAttribute('imageable_count'), fn () => $this->resource->imageable_count),
        ];
    }
}
