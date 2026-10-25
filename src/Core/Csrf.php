<?php

declare(strict_types=1);

namespace Clubmanager\Core;

/**
 * CSRF Protection
 *
 * Generates and verifies anti-Cross-Site Request Forgery tokens.
 *
 * @package Clubmanager\Core
 * @author  Reinhard Schiessl
 */
class Csrf
{
    /**
     * Generate or return existing CSRF token.
     *
     * @return string
     */
    public static function getToken(): string
    {
        $token = Session::get('_csrf_token');
        if (empty($token)) {
            $token = bin2hex(random_bytes(32));
            Session::set('_csrf_token', $token);
        }
        return $token;
    }

    /**
     * Validate an incoming CSRF token.
     *
     * @param string|null $token
     * @return bool
     */
    public static function validate(?string $token): bool
    {
        $sessionToken = Session::get('_csrf_token');
        if (empty($sessionToken) || empty($token)) {
            return false;
        }
        return hash_equals($sessionToken, $token);
    }

    /**
     * Generate HTML hidden input field with the token.
     *
     * @return string
     */
    public static function field(): string
    {
        $token = htmlspecialchars(static::getToken(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="_csrf_token" value="' . $token . '">';
    }
}
