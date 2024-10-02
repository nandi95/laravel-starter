<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\StringBackedEnum;

enum Role: string
{
    use StringBackedEnum;

    case Admin = 'admin';
    case User = 'user';
}
