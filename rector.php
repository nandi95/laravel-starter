<?php

declare(strict_types=1);

use Rector\CodingStyle\Rector\PostInc\PostIncDecToPreIncDecRector;
use Rector\Config\RectorConfig;
use Rector\Php81\Rector\Array_\FirstClassCallableRector;
use Rector\Privatization\Rector\Class_\FinalizeTestCaseClassRector;
use Rector\Transform\Rector\String_\StringToClassConstantRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use RectorLaravel\Set\LaravelLevelSetList;
use RectorLaravel\Set\LaravelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/bootstrap',
        __DIR__ . '/config',
        __DIR__ . '/resources',
        __DIR__ . '/routes',
        __DIR__ . '/tests',
        __DIR__ . '/database',
    ])
    ->withSkip([
        __DIR__ . '/bootstrap/cache',
        FirstClassCallableRector::class => [
            __DIR__ . '/routes',
        ],
        FinalizeTestCaseClassRector::class,
        PostIncDecToPreIncDecRector::class,
        StringToClassConstantRector::class => [
            __DIR__ . '/routes',
        ]
    ])
    // uncomment to reach your current PHP version
    ->withPhpSets()
    ->withSets([
        LaravelSetList::LARAVEL_110,
        LaravelLevelSetList::UP_TO_LARAVEL_110,
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        instanceOf: true,
        earlyReturn: true,
        // strictBooleans: true,
        carbon: true,
        rectorPreset: true,
        phpunitCodeQuality: true
    )
    ->withComposerBased(phpunit: true)
    ->withRules([
        AddVoidReturnTypeWhereNoReturnRector::class,
    ]);
