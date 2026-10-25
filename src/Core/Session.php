<?php

declare(strict_types=1);

namespace Clubmanager\Core;

/**
 * Session Management & Flash Messages
 *
 * @package Clubmanager\Core
 * @author  Reinhard Schiessl
 */
class Session
{
    /**
     * Start the PHP session securely if not already active.
     *
     * @return void
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            if (!headers_sent()) {
                $cookieParams = session_get_cookie_params();
                session_set_cookie_params([
                    'lifetime' => 86400 * 7, // 7 days
                    'path' => '/',
                    'domain' => $cookieParams['domain'],
                    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
                session_start();
            } else {
                @session_start();
            }
        }
    }

    /**
     * Get a session value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        static::start();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Set a session value.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function set(string $key, mixed $value): void
    {
        static::start();
        $_SESSION[$key] = $value;
    }

    /**
     * Check if a session key exists.
     *
     * @param string $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        static::start();
        return isset($_SESSION[$key]);
    }

    /**
     * Remove a session key.
     *
     * @param string $key
     * @return void
     */
    public static function remove(string $key): void
    {
        static::start();
        unset($_SESSION[$key]);
    }

    /**
     * Clear all session data.
     *
     * @return void
     */
    public static function destroy(): void
    {
        static::start();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();
    }

    /**
     * Flash a message for the next request.
     *
     * @param string $type ('success', 'danger', 'warning', 'info')
     * @param string $message
     * @return void
     */
    public static function flash(string $type, string $message): void
    {
        static::start();
        $_SESSION['_flashes'][$type][] = $message;
    }

    /**
     * Retrieve and clear all flashed messages.
     *
     * @return array<string, array<int, string>>
     */
    public static function getFlashes(): array
    {
        static::start();
        $flashes = $_SESSION['_flashes'] ?? [];
        unset($_SESSION['_flashes']);
        return $flashes;
    }
}
