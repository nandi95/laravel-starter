<?php

declare(strict_types=1);

namespace App;

use App\Models\Image;
use App\Models\Imageable;
use Barryvdh\LaravelIdeHelper\Console\ModelsCommand;
use Barryvdh\LaravelIdeHelper\Contracts\ModelHookInterface;
use Illuminate\Database\Eloquent\Model;

class ModelHook implements ModelHookInterface
{
    #[\Override]
    public function run(ModelsCommand $command, Model $model): void
    {
        if ($model instanceof Imageable) {
            $command->setProperty(
                'meta',
                'array{ main: ?boolean }',
                true,
                true
            );
        }

        if ($model instanceof Image) {
            $command->setProperty(
                name: 'pivot',
                type: Imageable::class,
                read: true,
                nullable: true
            );
        }
    }
}
