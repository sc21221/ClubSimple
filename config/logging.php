<?php

declare(strict_types=1);

/**
 * Logging Configuration
 *
 * Configurable structured logger compatible with Serilog JSON schema and standard log files.
 *
 * @package Clubmanager
 * @author  Reinhard Schiessl
 */

return [
    'path' => __DIR__ . '/../' . (getenv('LOG_PATH') ?: 'storage/logs/clubmanager.log'),
    'level' => getenv('LOG_LEVEL') ?: 'debug',
    'format' => 'json', // 'json' (Serilog compatible) or 'text'
    'max_files' => 30,
];
