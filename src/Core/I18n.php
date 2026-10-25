<?php

declare(strict_types=1);

namespace Clubmanager\Core;

/**
 * Internationalization (i18n) Service
 *
 * Manages multilingual UI translation strings with parameter interpolation.
 *
 * @package Clubmanager\Core
 * @author  Reinhard Schiessl
 */
class I18n
{
    /**
     * @var string Active locale code ('de' or 'en')
     */
    protected static string $locale = 'de';

    /**
     * @var array<string, array<string, string>> Loaded translation dictionaries
     */
    protected static array $translations = [];

    /**
     * Set the current active locale.
     *
     * @param string $locale
     * @return void
     */
    public static function setLocale(string $locale): void
    {
        $allowed = ['de', 'en'];
        $locale = strtolower(trim($locale));
        if (in_array($locale, $allowed, true)) {
            static::$locale = $locale;
        } else {
            static::$locale = 'de';
        }
    }

    /**
     * Get the current active locale.
     *
     * @return string
     */
    public static function getLocale(): string
    {
        return static::$locale;
    }

    /**
     * Translate a given key with optional placeholder replacements.
     *
     * @param string $key
     * @param array<string, mixed> $params
     * @param string|null $locale
     * @return string
     */
    public static function trans(string $key, array $params = [], ?string $locale = null): string
    {
        $targetLocale = $locale ?: static::$locale;
        static::load($targetLocale);

        $line = static::$translations[$targetLocale][$key] ?? null;

        // Fallback to German if key not found in chosen locale
        if ($line === null && $targetLocale !== 'de') {
            static::load('de');
            $line = static::$translations['de'][$key] ?? null;
        }

        if ($line === null) {
            return $key;
        }

        foreach ($params as $paramKey => $paramValue) {
            $line = str_replace(':' . $paramKey, (string) $paramValue, $line);
        }

        return $line;
    }

    /**
     * Load translation dictionary for a locale.
     *
     * @param string $locale
     * @return void
     */
    protected static function load(string $locale): void
    {
        if (isset(static::$translations[$locale])) {
            return;
        }

        $filePath = dirname(__DIR__, 2) . '/lang/' . $locale . '.php';
        if (file_exists($filePath)) {
            static::$translations[$locale] = require $filePath;
        } else {
            static::$translations[$locale] = [];
        }
    }
}
