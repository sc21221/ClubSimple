<?php

declare(strict_types=1);

namespace Clubmanager\Core;

use Clubmanager\Models\User;
use Clubmanager\Repositories\UserRepository;

/**
 * Authentication & Access Control Manager
 *
 * @package Clubmanager\Core
 * @author  Reinhard Schiessl
 */
class Auth
{
    protected static ?User $currentUser = null;

    /**
     * Attempt login with email, password, and chosen language.
     *
     * @param string $email
     * @param string $password
     * @param string $locale
     * @param bool $rememberMe
     * @return bool
     */
    public static function attempt(string $email, string $password, string $locale = 'de', bool $rememberMe = false): bool
    {
        $repo = new UserRepository();
        $user = $repo->findByEmail($email);

        if (!$user) {
            Logger::warning("Login failed: Unknown email address", ['email' => $email]);
            return false;
        }

        if (!$user->verifyPassword($password)) {
            Logger::warning("Login failed: Invalid password", ['email' => $email, 'user_id' => $user->id]);
            return false;
        }

        // Update user locale in database and set in i18n
        $user->locale = $locale;
        if ($rememberMe) {
            $token = bin2hex(random_bytes(32));
            $user->remember_token = $token;
            setcookie('clm_remember', $user->id . ':' . $token, time() + (86400 * 30), '/', '', isset($_SERVER['HTTPS']), true);
        }
        $user->save();

        I18n::setLocale($locale);

        static::login($user);

        Logger::info("User successfully signed in", [
            'UserId' => $user->id,
            'UserEmail' => $user->email,
            'Locale' => $locale,
        ]);

        return true;
    }

    public static function login(User $user): void
    {
        Session::start();
        if (!session_regenerate_id(true)) {
            throw new \RuntimeException('Unable to regenerate session ID');
        }
        Session::set('user_id', (int) $user->id);
        Session::set('user_name', $user->name);
        Session::set('user_email', $user->email);
        Session::set('user_admin', (bool) $user->admin);
        Session::set('user_locale', $user->locale ?: 'de');
        static::$currentUser = $user;
    }

    /**
     * Check if a user is currently logged in.
     *
     * @return bool
     */
    public static function check(): bool
    {
        return static::user() !== null;
    }

    /**
     * Get the currently authenticated user model.
     *
     * @return User|null
     */
    public static function user(): ?User
    {
        $userId = Session::get('user_id');
        if (!$userId) {
            static::$currentUser = null;
            return null;
        }

        static::$currentUser = User::find($userId);
        if (static::$currentUser === null) {
            foreach (['user_id', 'user_name', 'user_email', 'user_admin', 'user_locale'] as $key) {
                Session::remove($key);
            }
        }
        return static::$currentUser;
    }

    /**
     * Check if current user is an administrator.
     *
     * @return bool
     */
    public static function isAdmin(): bool
    {
        $user = static::user();
        return $user ? $user->isAdmin() : false;
    }

    /**
     * Log out current user and invalidate session and cookies.
     *
     * @return void
     */
    public static function logout(): void
    {
        $user = static::user();
        if ($user) {
            Logger::info("User signed out", ['UserId' => $user->id, 'UserEmail' => $user->email]);
            $user->remember_token = null;
            $user->save();
        }

        if (isset($_COOKIE['clm_remember'])) {
            setcookie('clm_remember', '', time() - 3600, '/');
        }

        Session::destroy();
        static::$currentUser = null;
    }
}
