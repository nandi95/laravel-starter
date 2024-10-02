<?php

declare(strict_types=1);

namespace App\Traits;

trait StringBackedEnum
{
    public static function values(): array
    {
        return array_map(static fn (self $enum) => $enum->value, static::cases());
    }
}
