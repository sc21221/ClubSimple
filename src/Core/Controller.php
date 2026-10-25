<?php

declare(strict_types=1);

namespace Clubmanager\Core;

use RuntimeException;

/**
 * Base Application Controller
 *
 * Provides template view rendering, layout orchestration,
 * response helpers, and CSRF token verification.
 *
 * @package Clubmanager\Core
 * @author  Reinhard Schiessl
 */
abstract class Controller
{
    /**
     * Render a view wrapped in a layout.
     *
     * @param string $view Relative view name (e.g. 'members/index')
     * @param array<string, mixed> $data
     * @param string|null $layout Layout name (e.g. 'main', 'auth') or null for no layout
     * @return void
     */
    public function render(string $view, array $data = [], ?string $layout = 'main'): void
    {
        // Extract data variables into local scope
        extract($data);

        // Global variables available in all views and layouts
        $currentUser = Auth::user();
        $isAdmin = Auth::isAdmin();
        $currentLocale = I18n::getLocale();
        $flashes = Session::getFlashes();
        $app = require dirname(__DIR__, 2) . '/config/app.php';

        $viewFile = dirname(__DIR__, 2) . '/views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            throw new RuntimeException("View file not found: {$viewFile}");
        }

        // Buffer the view content
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if ($layout === null) {
            echo $content;
            return;
        }

        $layoutFile = dirname(__DIR__, 2) . '/views/layouts/' . $layout . '.php';
        if (!file_exists($layoutFile)) {
            throw new RuntimeException("Layout file not found: {$layoutFile}");
        }

        require $layoutFile;
    }

    /**
     * Return JSON response.
     *
     * @param mixed $data
     * @param int $status
     * @return void
     */
    public function json(mixed $data, int $status = 200): void
    {
        Response::json($data, $status);
    }

    /**
     * Redirect to another URL.
     *
     * @param string $url
     * @param int $status
     * @return void
     */
    public function redirect(string $url, int $status = 302): void
    {
        Response::redirect($url, $status);
    }

    /**
     * Flash a feedback message for display in UI alerts.
     *
     * @param string $type ('success', 'danger', 'warning', 'info')
     * @param string $message
     * @return void
     */
    public function flash(string $type, string $message): void
    {
        Session::flash($type, $message);
    }

    /**
     * Validate CSRF token on POST requests.
     *
     * @param Request $request
     * @return bool
     */
    public function validateCsrf(Request $request): bool
    {
        $token = $request->post('_csrf_token') ?: $request->input('_csrf_token');
        if (!Csrf::validate($token)) {
            $this->flash('danger', 'Sicherheitstoken ungültig oder abgelaufen (CSRF). Bitte versuchen Sie es erneut.');
            $this->redirect($_SERVER['HTTP_REFERER'] ?? '/');
            return false;
        }
        return true;
    }
}
