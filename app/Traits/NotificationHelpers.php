<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\HtmlString;

trait NotificationHelpers
{
    public function shapeData(array $data): array
    {
        return [
            'title' => $this->title,
            'type' => class_basename(static::class),
            ...$data,
        ];
    }

    public function quote(string $text): HtmlString
    {
        $style = 'style="margin: 20px; padding: 10px; border-left: 3px solid #ccc; font-style: italic;"';

        return new HtmlString("<p $style>" . $text . '</p>');
    }

    public function bold(string $text): HtmlString
    {
        return new HtmlString('<strong>' . $text . '</strong>');
    }
}
