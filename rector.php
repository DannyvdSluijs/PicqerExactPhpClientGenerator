<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/resources',
        __DIR__ . '/src',
    ])
    // uncomment to reach your current PHP version
    ->withPhpLevel(PhpVersion::PHP_83)
    ->withTypeCoverageLevel(100);
