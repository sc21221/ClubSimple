<?php

declare(strict_types=1);

/**
 * Clubmanager - Pure PHP Web Application
 *
 * Front Controller and Application Bootstrap
 *
 * @package Clubmanager
 * @author  Reinhard Schiessl
 * @version 1.7
 * @date    16. Oktober 2025
 */

// 1. Load Composer Autoloader
$autoloader = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($autoloader)) {
    die("Autoloader not found. Please run 'composer dump-autoload'.");
}
require_once $autoloader;

// 2. Load .env Environment File
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v, " \t\n\r\0\x0B\"'");
            putenv("{$k}={$v}");
            $_ENV[$k] = $v;
            $_SERVER[$k] = $v;
        }
    }
}

// 3. Error Reporting & Debug Mode
$isDebug = (bool) (getenv('APP_DEBUG') ?: true);
if ($isDebug) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// 4. Initialize Structured Serilog Logger
$logConfig = require dirname(__DIR__) . '/config/logging.php';
\Clubmanager\Core\Logger::init($logConfig['path'], $logConfig['level']);

// Global uncaught exception and error handler
set_exception_handler(function (\Throwable $e) use ($isDebug) {
    \Clubmanager\Core\Logger::error("Uncaught exception: " . $e->getMessage(), [
        'Exception' => get_class($e),
        'File' => $e->getFile(),
        'Line' => $e->getLine(),
        'Trace' => $e->getTraceAsString(),
    ]);

    http_response_code(500);
    if ($isDebug) {
        echo "<div style='font-family:sans-serif; padding:2rem; background:#fee; border-left:5px solid red;'>";
        echo "<h2>Application Error</h2><p><strong>" . htmlspecialchars($e->getMessage()) . "</strong></p>";
        echo "<pre style='background:#f8f9fa; padding:1rem; border:1px solid #ddd;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        echo "</div>";
    } else {
        require dirname(__DIR__) . '/views/errors/500.php';
    }
    exit;
});

// 5. Initialize Database & Connect
try {
    \Clubmanager\Core\Database::getConnection();
} catch (\Throwable $e) {
    \Clubmanager\Core\Logger::error("Database boot failure: " . $e->getMessage());
    die("Database connection failed. Check storage/logs/clubmanager.log for details.");
}

// 6. Start Session and Set Language
\Clubmanager\Core\Session::start();

// Handle Remember-Me Cookie
if (!\Clubmanager\Core\Auth::check() && !empty($_COOKIE['clm_remember'])) {
    [$userId, $token] = explode(':', $_COOKIE['clm_remember'], 2) + [null, null];
    if ($userId && $token) {
        $user = \Clubmanager\Models\User::find($userId);
        if ($user && $user->remember_token && hash_equals($user->remember_token, $token)) {
            \Clubmanager\Core\Session::set('user_id', (int) $user->id);
            \Clubmanager\Core\Session::set('user_name', $user->name);
            \Clubmanager\Core\Session::set('user_email', $user->email);
            \Clubmanager\Core\Session::set('user_admin', (bool) $user->admin);
            \Clubmanager\Core\Session::set('user_locale', $user->locale ?: 'de');
        }
    }
}

// Determine active UI language
$currentLocale = \Clubmanager\Core\Session::get('user_locale', getenv('APP_DEFAULT_LOCALE') ?: 'de');
\Clubmanager\Core\I18n::setLocale($currentLocale);

// 7. Initialize Router & Define Routes
$router = new \Clubmanager\Core\Router();

// Root Redirect
$router->get('/', function ($request) {
    if (\Clubmanager\Core\Auth::check()) {
        \Clubmanager\Core\Response::redirect('/dashboard');
    } else {
        \Clubmanager\Core\Response::redirect('/login');
    }
});

// Authentication Routes
$router->get('/login', [\Clubmanager\Controllers\AuthController::class, 'showLogin'], ['guest']);
$router->post('/login', [\Clubmanager\Controllers\AuthController::class, 'login']);
$router->post('/logout', [\Clubmanager\Controllers\AuthController::class, 'logout'], ['auth']);
$router->post('/auth/lang', [\Clubmanager\Controllers\AuthController::class, 'switchLanguage']);

// Dashboard
$router->get('/dashboard', [\Clubmanager\Controllers\DashboardController::class, 'index'], ['auth']);

// Member Management
$router->get('/members', [\Clubmanager\Controllers\MemberController::class, 'index'], ['auth']);
$router->get('/members/create', [\Clubmanager\Controllers\MemberController::class, 'create'], ['auth']);
$router->post('/members', [\Clubmanager\Controllers\MemberController::class, 'store'], ['auth']);
$router->get('/members/{id}', [\Clubmanager\Controllers\MemberController::class, 'show'], ['auth']);
$router->get('/members/{id}/edit', [\Clubmanager\Controllers\MemberController::class, 'edit'], ['auth']);
$router->post('/members/{id}', [\Clubmanager\Controllers\MemberController::class, 'update'], ['auth']);
$router->post('/members/{id}/archive', [\Clubmanager\Controllers\MemberController::class, 'archive'], ['auth']);
$router->post('/members/{id}/delete', [\Clubmanager\Controllers\MemberController::class, 'delete'], ['auth']);
$router->post('/members/{id}/fee', [\Clubmanager\Controllers\MemberController::class, 'assignFeeClass'], ['auth']);
$router->post('/members/{id}/fee/{assignmentId}/update', [\Clubmanager\Controllers\MemberController::class, 'updateFeeClass'], ['auth']);
$router->post('/members/{id}/fee/{assignmentId}/delete', [\Clubmanager\Controllers\MemberController::class, 'removeFeeClass'], ['auth']);
$router->post('/members/{id}/departments', [\Clubmanager\Controllers\DepartmentController::class, 'assign'], ['auth']);
$router->post('/members/{id}/departments/{assignmentId}/delete', [\Clubmanager\Controllers\DepartmentController::class, 'remove'], ['auth']);
$router->get('/members/{id}/export/json', [\Clubmanager\Controllers\MemberController::class, 'exportGdprJson'], ['auth']);
$router->get('/members/{id}/export/pdf', [\Clubmanager\Controllers\MemberController::class, 'exportGdprPdf'], ['auth']);
$router->post('/members/{id}/anonymize', [\Clubmanager\Controllers\MemberController::class, 'anonymize'], ['auth']);

// Departments
$router->get('/departments', [\Clubmanager\Controllers\DepartmentController::class, 'index'], ['auth']);
$router->get('/departments/create', [\Clubmanager\Controllers\DepartmentController::class, 'create'], ['auth']);
$router->post('/departments', [\Clubmanager\Controllers\DepartmentController::class, 'store'], ['auth']);
$router->get('/departments/{id}/edit', [\Clubmanager\Controllers\DepartmentController::class, 'edit'], ['auth']);
$router->post('/departments/{id}/update', [\Clubmanager\Controllers\DepartmentController::class, 'update'], ['auth']);
$router->post('/departments/{id}/delete', [\Clubmanager\Controllers\DepartmentController::class, 'delete'], ['auth']);
$router->get('/departments/{id}/members', [\Clubmanager\Controllers\DepartmentController::class, 'show'], ['auth']);

// Fee Classes
$router->get('/fee-classes', [\Clubmanager\Controllers\FeeClassController::class, 'index'], ['auth']);
$router->get('/fee-classes/create', [\Clubmanager\Controllers\FeeClassController::class, 'create'], ['auth']);
$router->post('/fee-classes', [\Clubmanager\Controllers\FeeClassController::class, 'store'], ['auth']);
$router->get('/fee-classes/{id}/edit', [\Clubmanager\Controllers\FeeClassController::class, 'edit'], ['auth']);
$router->post('/fee-classes/{id}', [\Clubmanager\Controllers\FeeClassController::class, 'update'], ['auth']);
$router->post('/fee-classes/{id}/delete', [\Clubmanager\Controllers\FeeClassController::class, 'delete'], ['auth']);

// SEPA Direct Debit Processing
$router->get('/sepa', [\Clubmanager\Controllers\SepaController::class, 'index'], ['auth']);
$router->get('/sepa/preview', [\Clubmanager\Controllers\SepaController::class, 'preview'], ['auth']);
$router->post('/sepa/execute', [\Clubmanager\Controllers\SepaController::class, 'execute'], ['auth']);
$router->get('/sepa/download', [\Clubmanager\Controllers\SepaController::class, 'download'], ['auth']);
$router->get('/sepa/history', [\Clubmanager\Controllers\SepaController::class, 'history'], ['auth']);

// Reports & Anniversary Lists
$router->get('/reports/birthdays', [\Clubmanager\Controllers\ReportController::class, 'birthdays'], ['auth']);
$router->get('/reports/anniversaries', [\Clubmanager\Controllers\ReportController::class, 'anniversaries'], ['auth']);

// Administrator Routes (RBAC: Requires users.admin = 1)
$router->get('/users', [\Clubmanager\Controllers\UserController::class, 'index'], ['admin']);
$router->get('/users/create', [\Clubmanager\Controllers\UserController::class, 'create'], ['admin']);
$router->post('/users', [\Clubmanager\Controllers\UserController::class, 'store'], ['admin']);
$router->get('/users/{id}/edit', [\Clubmanager\Controllers\UserController::class, 'edit'], ['admin']);
$router->post('/users/{id}', [\Clubmanager\Controllers\UserController::class, 'update'], ['admin']);
$router->post('/users/{id}/delete', [\Clubmanager\Controllers\UserController::class, 'delete'], ['admin']);

$router->get('/club', [\Clubmanager\Controllers\ClubController::class, 'edit'], ['admin']);
$router->post('/club', [\Clubmanager\Controllers\ClubController::class, 'update'], ['admin']);

$router->get('/audit', [\Clubmanager\Controllers\AuditLogController::class, 'index'], ['admin']);

// 8. Dispatch Request
$request = new \Clubmanager\Core\Request();
$router->dispatch($request);
