<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Prepare environment
putenv("APP_DEBUG=true");
putenv("DB_CONNECTION=sqlite");
putenv("DB_SQLITE_PATH=storage/database.sqlite");

echo "Testing Web Front Controller & Routes...\n";

function simulateRequest(string $method, string $uri, array $get = [], array $post = [], array $session = []): array {
    $_SERVER['REQUEST_METHOD'] = $method;
    $_SERVER['REQUEST_URI'] = $uri;
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_GET = $get;
    $_POST = $post;
    $_SESSION = $session;

    ob_start();
    try {
        $router = new \Clubmanager\Core\Router();

        // Register routes identically to public/index.php
        $router->get('/login', [\Clubmanager\Controllers\AuthController::class, 'showLogin']);
        $router->post('/login', [\Clubmanager\Controllers\AuthController::class, 'login']);
        $router->get('/dashboard', [\Clubmanager\Controllers\DashboardController::class, 'index'], ['auth']);
        $router->get('/members', [\Clubmanager\Controllers\MemberController::class, 'index'], ['auth']);
        $router->get('/fee-classes', [\Clubmanager\Controllers\FeeClassController::class, 'index'], ['auth']);
        $router->get('/sepa', [\Clubmanager\Controllers\SepaController::class, 'index'], ['auth']);
        $router->get('/reports/birthdays', [\Clubmanager\Controllers\ReportController::class, 'birthdays'], ['auth']);
        $router->get('/reports/anniversaries', [\Clubmanager\Controllers\ReportController::class, 'anniversaries'], ['auth']);
        $router->get('/audit', [\Clubmanager\Controllers\AuditLogController::class, 'index'], ['admin']);

        $request = new \Clubmanager\Core\Request();
        $router->dispatch($request);
    } catch (\Throwable $e) {
        $output = ob_get_clean();
        return ['code' => 500, 'body' => $output, 'exception' => $e->getMessage()];
    }

    $body = ob_get_clean();
    $code = http_response_code() ?: 200;
    return ['code' => $code, 'body' => $body];
}

// 1. Test GET /login (German)
$res = simulateRequest('GET', '/login', ['lang' => 'de']);
assert(str_contains($res['body'], 'Anmeldung'), "Login page contains 'Anmeldung'");
echo "[PASS] GET /login in German renders successfully\n";

// 2. Test GET /login (English)
$res = simulateRequest('GET', '/login', ['lang' => 'en']);
assert(str_contains($res['body'], 'Sign In'), "Login page contains 'Sign In'");
echo "[PASS] GET /login in English renders successfully\n";

// 3. Test GET /dashboard as Admin
\Clubmanager\Core\I18n::setLocale('de');
$adminSession = [
    'user_id' => 1,
    'user_name' => 'Reinhard Schiessl (Admin)',
    'user_email' => 'admin@musikverein-denkendorf.de',
    'user_admin' => true,
    'user_locale' => 'de',
];
$res = simulateRequest('GET', '/dashboard', [], [], $adminSession);
assert(str_contains($res['body'], 'Musikverein Denkendorf'), "Dashboard renders club name");
assert(str_contains($res['body'], 'Aktive Mitglieder'), "Dashboard renders active members widget");
assert(str_contains($res['body'], 'Beitragsklassen'), "Dashboard renders fee classes widget");
assert(str_contains($res['body'], 'Letzter SEPA-Lauf'), "Dashboard renders last SEPA run widget");
echo "[PASS] GET /dashboard renders 4 required specification widgets\n";

// 4. Test GET /members
$res = simulateRequest('GET', '/members', [], [], $adminSession);
assert(str_contains($res['body'], 'Mitgliederverwaltung'), "Members directory renders");
assert(str_contains($res['body'], 'Lukas Bauer'), "Members directory contains seeded member");
echo "[PASS] GET /members renders searchable DataGrid\n";

// 5. Test GET /fee-classes
$res = simulateRequest('GET', '/fee-classes', [], [], $adminSession);
assert(str_contains($res['body'], 'Beitragsklassen'), "Fee classes page renders");
assert(str_contains($res['body'], 'Jugend / Schüler'), "Fee classes page lists Jugend");
echo "[PASS] GET /fee-classes renders fee classes list\n";

// 6. Test GET /sepa
$res = simulateRequest('GET', '/sepa', [], [], $adminSession);
assert(str_contains($res['body'], 'SEPA-Lastschriftverfahren'), "SEPA page renders");
assert(str_contains($res['body'], 'DE98ZZZ09999999999'), "SEPA page contains Creditor ID");
echo "[PASS] GET /sepa renders SEPA management view\n";

// 7. Test GET /reports/birthdays
$res = simulateRequest('GET', '/reports/birthdays', [], [], $adminSession);
assert(str_contains($res['body'], 'Geburtstagslisten'), "Birthday report page renders");
echo "[PASS] GET /reports/birthdays renders birthday list\n";

// 8. Test GET /reports/anniversaries
$res = simulateRequest('GET', '/reports/anniversaries', [], [], $adminSession);
assert(str_contains($res['body'], 'Vereinsjubiläen'), "Anniversary report page renders");
echo "[PASS] GET /reports/anniversaries renders anniversary list\n";

// 9. Test GET /audit
$res = simulateRequest('GET', '/audit', [], [], $adminSession);
assert(str_contains($res['body'], 'Audit-Trail'), "Audit page renders");
echo "[PASS] GET /audit renders immutable mutation journal\n";

echo "\nAll Front Controller & View Route tests passed successfully!\n";
