<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphPivot;

/**
 * @mixin IdeHelperImageable
 */
class Imageable extends MorphPivot
{
    #[\Override]
    protected function casts(): array
    {
        return [
            'meta' => 'array'
        ];
    }
}
