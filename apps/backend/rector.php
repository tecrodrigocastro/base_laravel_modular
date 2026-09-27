<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use RectorLaravel\Set\LaravelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/database',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    ->withSkip([
        __DIR__.'/vendor',
    ])
    ->withPhpSets(php83: true)
    ->withSets([
        LevelSetList::UP_TO_PHP_83,
        LaravelSetList::LARAVEL_CODE_QUALITY,
    ])
    ->withImportNames(removeUnusedImports: true);
