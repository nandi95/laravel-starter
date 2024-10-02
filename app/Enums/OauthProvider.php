<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\StringBackedEnum;

enum OauthProvider: string
{
    use StringBackedEnum;

    case FACEBOOK = 'facebook';

    case GOOGLE = 'google';
}
