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
use Clubmanager\Controllers\AuditLogController;
use Clubmanager\Controllers\AuthController;
use Clubmanager\Controllers\ClubController;
use Clubmanager\Controllers\DashboardController;
use Clubmanager\Controllers\DepartmentController;
use Clubmanager\Controllers\EventController;
use Clubmanager\Controllers\FeeClassController;
use Clubmanager\Controllers\FunctionController;
use Clubmanager\Controllers\MemberController;
use Clubmanager\Controllers\ReportController;
use Clubmanager\Controllers\SepaController;
use Clubmanager\Controllers\UserController;
use Clubmanager\Core\Auth;
use Clubmanager\Core\Database;
use Clubmanager\Core\I18n;
use Clubmanager\Core\Logger;
use Clubmanager\Core\Request;
use Clubmanager\Core\Response;
use Clubmanager\Core\Router;
use Clubmanager\Core\Session;
use Clubmanager\Models\User;

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
$isDebug = getenv('APP_DEBUG') === 'true';
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
Logger::init($logConfig['path'], $logConfig['level']);

// Global uncaught exception and error handler
set_exception_handler(function (\Throwable $e) use ($isDebug) {
    Logger::error("Uncaught exception: " . $e->getMessage(), [
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
    Database::getConnection();
} catch (\Throwable $e) {
    Logger::error("Database boot failure: " . $e->getMessage());
    die("Database connection failed. Check storage/logs/clubmanager.log for details.");
}

// 6. Start Session and Set Language
Session::start();

// Handle Remember-Me Cookie
if (!Auth::check() && !empty($_COOKIE['clm_remember'])) {
    [$userId, $token] = explode(':', $_COOKIE['clm_remember'], 2) + [null, null];
    if ($userId && $token) {
        $user = User::find($userId);
        if ($user && $user->remember_token && hash_equals($user->remember_token, $token)) {
            Auth::login($user);
        }
    }
}

// Determine active UI language
$currentLocale = Session::get('user_locale', getenv('APP_DEFAULT_LOCALE') ?: 'de');
I18n::setLocale($currentLocale);

// 7. Initialize Router & Define Routes
$router = new Router();

// Root Redirect
$router->get('/', function ($request) {
    if (Auth::check()) {
        Response::redirect('/dashboard');
    } else {
        Response::redirect('/login');
    }
});

// Authentication Routes
$router->get('/login', [AuthController::class, 'showLogin'], ['guest']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout'], ['auth']);
$router->post('/auth/lang', [AuthController::class, 'switchLanguage']);

// Dashboard
$router->get('/dashboard', [DashboardController::class, 'index'], ['auth']);

// Member Management
$router->get('/members', [MemberController::class, 'index'], ['auth']);
$router->get('/members/create', [MemberController::class, 'create'], ['auth']);
$router->post('/members', [MemberController::class, 'store'], ['auth']);
$router->get('/members/{id}', [MemberController::class, 'show'], ['auth']);
$router->get('/members/{id}/edit', [MemberController::class, 'edit'], ['auth']);
$router->post('/members/{id}', [MemberController::class, 'update'], ['auth']);
$router->post('/members/{id}/archive', [MemberController::class, 'archive'], ['auth']);
$router->post('/members/{id}/delete', [MemberController::class, 'delete'], ['auth']);
$router->post('/members/{id}/fee', [MemberController::class, 'assignFeeClass'], ['auth']);
$router->post('/members/{id}/fee/{assignmentId}/update', [MemberController::class, 'updateFeeClass'], ['auth']);
$router->post('/members/{id}/fee/{assignmentId}/delete', [MemberController::class, 'removeFeeClass'], ['auth']);
$router->post('/members/{id}/departments', [DepartmentController::class, 'assign'], ['auth']);
$router->post('/members/{id}/departments/{assignmentId}/delete', [DepartmentController::class, 'remove'], ['auth']);
$router->post('/members/{id}/functions', [FunctionController::class, 'assign'], ['auth']);
$router->post('/members/{id}/functions/{assignmentId}/delete', [FunctionController::class, 'remove'], ['auth']);
$router->post('/members/{id}/events', [EventController::class, 'assign'], ['auth']);
$router->post('/members/{id}/events/{assignmentId}/delete', [EventController::class, 'remove'], ['auth']);
$router->get('/members/{id}/export/json', [MemberController::class, 'exportGdprJson'], ['auth']);
$router->get('/members/{id}/export/pdf', [MemberController::class, 'exportGdprPdf'], ['auth']);
$router->post('/members/{id}/anonymize', [MemberController::class, 'anonymize'], ['auth']);

// Departments
$router->get('/departments', [DepartmentController::class, 'index'], ['auth']);
$router->get('/departments/create', [DepartmentController::class, 'create'], ['auth']);
$router->post('/departments', [DepartmentController::class, 'store'], ['auth']);
$router->get('/departments/{id}/edit', [DepartmentController::class, 'edit'], ['auth']);
$router->post('/departments/{id}/update', [DepartmentController::class, 'update'], ['auth']);
$router->post('/departments/{id}/delete', [DepartmentController::class, 'delete'], ['auth']);
$router->get('/departments/{id}/members', [DepartmentController::class, 'show'], ['auth']);

// Functions
$router->get('/functions', [FunctionController::class, 'index'], ['auth']);
$router->get('/functions/create', [FunctionController::class, 'create'], ['auth']);
$router->post('/functions', [FunctionController::class, 'store'], ['auth']);
$router->get('/functions/{id}/edit', [FunctionController::class, 'edit'], ['auth']);
$router->post('/functions/{id}/update', [FunctionController::class, 'update'], ['auth']);
$router->post('/functions/{id}/delete', [FunctionController::class, 'delete'], ['auth']);
$router->get('/functions/{id}/members', [FunctionController::class, 'members'], ['auth']);
$router->get('/functions/{id}/history', [FunctionController::class, 'history'], ['auth']);

// Events
$router->get('/events', [EventController::class, 'index'], ['auth']);
$router->get('/events/create', [EventController::class, 'create'], ['auth']);
$router->post('/events', [EventController::class, 'store'], ['auth']);
$router->get('/events/{id}/edit', [EventController::class, 'edit'], ['auth']);
$router->post('/events/{id}/update', [EventController::class, 'update'], ['auth']);
$router->post('/events/{id}/delete', [EventController::class, 'delete'], ['auth']);
$router->get('/events/{id}/members', [EventController::class, 'members'], ['auth']);

// Fee Classes
$router->get('/fee-classes', [FeeClassController::class, 'index'], ['auth']);
$router->get('/fee-classes/create', [FeeClassController::class, 'create'], ['auth']);
$router->post('/fee-classes', [FeeClassController::class, 'store'], ['auth']);
$router->get('/fee-classes/{id}/edit', [FeeClassController::class, 'edit'], ['auth']);
$router->post('/fee-classes/{id}', [FeeClassController::class, 'update'], ['auth']);
$router->post('/fee-classes/{id}/delete', [FeeClassController::class, 'delete'], ['auth']);

// SEPA Direct Debit Processing
$router->get('/sepa', [SepaController::class, 'index'], ['auth']);
$router->get('/sepa/preview', [SepaController::class, 'preview'], ['auth']);
$router->post('/sepa/execute', [SepaController::class, 'execute'], ['auth']);
$router->get('/sepa/download', [SepaController::class, 'download'], ['auth']);
$router->get('/sepa/history', [SepaController::class, 'history'], ['auth']);

// Reports & Anniversary Lists
$router->get('/reports/birthdays', [ReportController::class, 'birthdays'], ['auth']);
$router->get('/reports/anniversaries', [ReportController::class, 'anniversaries'], ['auth']);

// Administrator Routes (RBAC: Requires users.admin = 1)
$router->get('/users', [UserController::class, 'index'], ['admin']);
$router->get('/users/create', [UserController::class, 'create'], ['admin']);
$router->post('/users', [UserController::class, 'store'], ['admin']);
$router->get('/users/{id}/edit', [UserController::class, 'edit'], ['admin']);
$router->post('/users/{id}', [UserController::class, 'update'], ['admin']);
$router->post('/users/{id}/delete', [UserController::class, 'delete'], ['admin']);

$router->get('/club', [ClubController::class, 'edit'], ['admin']);
$router->post('/club', [ClubController::class, 'update'], ['admin']);

$router->get('/audit', [AuditLogController::class, 'index'], ['admin']);

// 8. Dispatch Request
$request = new Request();
$router->dispatch($request);
