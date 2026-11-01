<?php

declare(strict_types=1);

namespace Clubmanager\Controllers;

use Clubmanager\Core\Controller;
use Clubmanager\Core\Request;
use Clubmanager\Core\Auth;
use Clubmanager\Core\I18n;
use Clubmanager\Core\Session;

/**
 * Authentication Controller
 *
 * Handles user sign in, language selection persistence, and session termination.
 *
 * @package Clubmanager\Controllers
 * @author  Reinhard Schiessl
 */
class AuthController extends Controller
{
    /**
     * Display the login page.
     *
     * @param Request $request
     * @return void
     */
    public function showLogin(Request $request): void
    {
        $selectedLocale = $request->query('lang') ?: Session::get('user_locale', 'de');
        I18n::setLocale($selectedLocale);

        $this->render('auth/login', [
            'locale' => $selectedLocale,
        ], 'auth');
    }

    /**
     * Authenticate credentials.
     *
     * @param Request $request
     * @return void
     */
    public function login(Request $request): void
    {
        if (!$this->validateCsrf($request)) {
            return;
        }

        $email = (string) $request->post('email', '');
        $password = (string) $request->post('password', '');
        $locale = (string) $request->post('locale', 'de');
        $remember = (bool) $request->post('remember_me', false);

        I18n::setLocale($locale);

        if (Auth::attempt($email, $password, $locale, $remember)) {
            $this->flash('success', __('dashboard_welcome') . ', ' . e(Auth::user()->name) . '!');
            $this->redirect('/dashboard');
        } else {
            $this->flash('danger', __('login_failed'));
            $this->redirect('/login?lang=' . urlencode($locale));
        }
    }

    /**
     * Switch UI language and redirect back.
     *
     * @param Request $request
     * @return void
     */
    public function switchLanguage(Request $request): void
    {
        if (!$this->validateCsrf($request)) {
            return;
        }

        $locale = $request->post('lang', 'de');
        I18n::setLocale($locale);
        $locale = I18n::getLocale();
        Session::set('user_locale', $locale);

        $user = Auth::user();
        if ($user) {
            $user->locale = $locale;
            $user->save();
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? '/dashboard';
        $this->redirect($referer);
    }

    /**
     * Log out user.
     *
     * @param Request $request
     * @return void
     */
    public function logout(Request $request): void
    {
        if (!$this->validateCsrf($request)) {
            return;
        }

        Auth::logout();
        $this->flash('success', __('logout_success'));
        $this->redirect('/login');
    }
}
