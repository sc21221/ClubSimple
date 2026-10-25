<?php

declare(strict_types=1);

use Clubmanager\Core\I18n;
use Clubmanager\Core\Csrf;
use Clubmanager\Core\Session;

if (!function_exists('__')) {
    /**
     * Translate string.
     *
     * @param string $key
     * @param array<string, mixed> $params
     * @param string|null $locale
     * @return string
     */
    function __(string $key, array $params = [], ?string $locale = null): string
    {
        return I18n::trans($key, $params, $locale);
    }
}

if (!function_exists('e')) {
    /**
     * Escape HTML string safely.
     *
     * @param mixed $value
     * @return string
     */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Get current CSRF token string.
     *
     * @return string
     */
    function csrf_token(): string
    {
        return Csrf::getToken();
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Generate HTML input for CSRF token.
     *
     * @return string
     */
    function csrf_field(): string
    {
        return Csrf::field();
    }
}

if (!function_exists('old')) {
    /**
     * Retrieve flashed old input or default.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function old(string $key, mixed $default = null): mixed
    {
        $oldInputs = Session::get('_old_input', []);
        return $oldInputs[$key] ?? $default;
    }
}

if (!function_exists('format_money')) {
    /**
     * Format currency amount for display.
     *
     * @param float|int|string|null $amount
     * @param string $currency
     * @return string
     */
    function format_money(float|int|string|null $amount, string $currency = '€'): string
    {
        $num = (float) ($amount ?? 0.0);
        return number_format($num, 2, ',', '.') . ' ' . $currency;
    }
}

if (!function_exists('format_date')) {
    /**
     * Format date string according to active locale.
     *
     * @param string|null $date
     * @param string $format
     * @return string
     */
    function format_date(?string $date, string $format = 'd.m.Y'): string
    {
        if (empty($date) || $date === '0000-00-00') {
            return '-';
        }
        try {
            $dt = new \DateTimeImmutable($date);
            return $dt->format($format);
        } catch (\Exception $e) {
            return $date;
        }
    }
}

if (!function_exists('format_iban')) {
    /**
     * Format IBAN in standard 4-character spaced groups.
     *
     * @param string|null $iban
     * @return string
     */
    function format_iban(?string $iban): string
    {
        if (empty($iban)) {
            return '';
        }
        $clean = strtoupper(preg_replace('/\s+/', '', $iban));
        return chunk_split($clean, 4, ' ');
    }
}
