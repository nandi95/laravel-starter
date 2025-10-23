<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements CastsAttributes<float, int>
 */
class Monetary implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    #[\Override]
    public function get(Model $model, string $key, mixed $value, array $attributes): float
    {
        return (int) $value / 100;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    #[\Override]
    public function set(Model $model, string $key, mixed $value, array $attributes): int
    {
        return (int) $value * 100;
    }
}
