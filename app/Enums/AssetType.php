<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Str;

enum AssetType: string
{
    case Image = 'image';
    case Video = 'video';

    public function toStoragePath(): string
    {
        return Str::plural(Str::lower($this->value));
    }

    public static function fromMime(string $mime): self
    {
        [$type, $subType] = explode('/', $mime);

        return match ($type) {
            'image' => self::Image,
            'video' => self::Video,
            default => throw new \InvalidArgumentException('Unsupported mime type')
        };
    }
}
