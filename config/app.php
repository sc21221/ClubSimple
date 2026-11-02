<?php

declare(strict_types=1);

/**
 * Clubmanager Application Configuration
 *
 * @package Clubmanager
 * @author  Reinhard Schiessl
 * @version 1.7
 * @date    16. Oktober 2025
 */

return [
    'name' => getenv('APP_NAME') ?: 'Clubmanager',
    'author' => 'Reinhard Schiessl',
    'version' => '1.7',
    'date' => '16. Oktober 2025',
    'env' => getenv('APP_ENV') ?: 'production',
    'debug' => getenv('APP_DEBUG') === 'true',
    'url' => getenv('APP_URL') ?: 'http://localhost:8000',
    'default_locale' => getenv('APP_DEFAULT_LOCALE') ?: 'de',
    'locales' => [
        'de' => 'Deutsch',
        'en' => 'English',
    ],
    'club_default_name' => 'Musikverein Denkendorf',
];
