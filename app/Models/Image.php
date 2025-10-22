<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @mixin IdeHelperImage
 */
class Image extends Model
{
    protected $fillable = [
        'imageable_type',
        'imageable_id',
        'mime_type',
        'size',
        'width',
        'height',
        'title',
        'storage_location'
    ];

    /**
     * {@inheritDoc}
     */
    protected $appends = [
        'source'
    ];

    /**
     * {@inheritDoc}
     */
    protected $hidden = [
        'storage_location'
    ];

    public function scopeWithRelatedCount(Builder $query): Builder
    {
        if (is_null($query->getQuery()->columns)) {
            $query->select($query->getQuery()->from . '.*');
        }

        $query->selectSub(function (\Illuminate\Database\Query\Builder $query): void {
            $query->selectRaw('count(*)')
                ->from('imageables')
                ->whereColumn('imageables.image_id', 'images.id');
        }, 'imageable_count');

        return $query;
    }

    /**
     * Related records.
     */
    public function imageables(): MorphToMany
    {
        // return a union of relationships here
        return $this->morphedByMany('todo-replace-me', 'imageable');
    }

    /**
     * Get the image url.
     */
    protected function source(): Attribute
    {
        return Attribute::make(
            get: static function (mixed $value, array $attributes) {
                $location = $attributes['storage_location'];

                if (Str::is('https://loremflickr.com/*', $location)) {
                    return $location;
                }

                // using bunny cdn
                //                return "https://some-cdn.b-cdn.net/$location";
                return Storage::url($location);
            }
        );
    }
}
