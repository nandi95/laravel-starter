<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Image;
use App\Models\Imageable;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait InteractsWithImages
{
    public function images(): MorphToMany
    {
        return $this->morphToMany(Image::class, 'imageable')
            ->using(Imageable::class)
            ->withPivot('meta');
    }
}
