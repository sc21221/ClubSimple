<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Clubmanager\Controllers\AuthController;
use Clubmanager\Core\Auth;
use Clubmanager\Core\Database;
use Clubmanager\Core\I18n;
use Clubmanager\Core\Request;
use Clubmanager\Core\Session;

class Redirected extends RuntimeException {}

class TestAuthController extends AuthController
{
    public function redirect(string $url, int $status = 302): void
    {
        throw new Redirected($url);
    }
}

Session::start();
$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT, locale TEXT, remember_token TEXT)");
(new ReflectionProperty(Database::class, 'instance'))->setValue(null, $db);
$cachedUser = new ReflectionProperty(Auth::class, 'currentUser');
$failures = [];

function check(bool $condition, string $message): void
{
    global $failures;
    if (!$condition) {
        $failures[] = $message;
    }
}

function invokeAction(string $action, array $post, array $query = []): string
{
    $_POST = $post;
    $_GET = $query;
    try {
        (new TestAuthController())->$action(new Request());
    } catch (Redirected $e) {
        return $e->getMessage();
    }
    return '';
}

foreach (['missing' => [], 'invalid' => ['_csrf_token' => 'wrong']] as $label => $token) {
    $db->exec("DELETE FROM users; INSERT INTO users VALUES (1, 'Test', 'test@example.com', 'de', 'remember')");
    $_SESSION = ['user_id' => 1, 'user_locale' => 'de', '_csrf_token' => 'valid'];
    $cachedUser->setValue(null, null);
    invokeAction('logout', $token);
    check(Auth::check(), "Logout with $label token must preserve authentication");
    check($db->query('SELECT remember_token FROM users WHERE id = 1')->fetchColumn() === 'remember', "Logout with $label token must preserve remember token");

    $_SESSION = ['user_id' => 1, 'user_locale' => 'de', '_csrf_token' => 'valid'];
    $cachedUser->setValue(null, null);
    I18n::setLocale('de');
    invokeAction('switchLanguage', $token + ['lang' => 'en'], ['lang' => 'en']);
    check($_SESSION['user_locale'] === 'de', "Language switch with $label token must preserve session locale");
    check($db->query('SELECT locale FROM users WHERE id = 1')->fetchColumn() === 'de', "Language switch with $label token must preserve profile locale");
}

$_SESSION = ['user_id' => 1, 'user_locale' => 'de', '_csrf_token' => 'valid'];
$cachedUser->setValue(null, null);
invokeAction('switchLanguage', ['_csrf_token' => 'valid', 'lang' => 'en']);
check($_SESSION['user_locale'] === 'en', 'Valid language switch updates session');
check($db->query('SELECT locale FROM users WHERE id = 1')->fetchColumn() === 'en', 'Valid language switch updates profile');

invokeAction('logout', ['_csrf_token' => 'valid']);
check(!Auth::check(), 'Valid logout clears authentication');
check($db->query('SELECT remember_token FROM users WHERE id = 1')->fetchColumn() === null, 'Valid logout clears remember token');

$_SESSION = ['user_locale' => 'de', '_csrf_token' => 'valid'];
$cachedUser->setValue(null, null);
invokeAction('switchLanguage', ['_csrf_token' => 'valid', 'lang' => 'en']);
check($_SESSION['user_locale'] === 'en', 'Guest can switch language');
invokeAction('switchLanguage', ['_csrf_token' => 'valid', 'lang' => 'unsupported']);
check($_SESSION['user_locale'] === 'de', 'Unsupported language uses the normalized fallback');

$routes = file_get_contents(__DIR__ . '/../public/index.php');
check(str_contains($routes, "\$router->post('/auth/lang'"), 'Language route must use POST');
check(!str_contains($routes, "\$router->get('/auth/lang'"), 'Language route must not use GET');

$currentLocale = 'de';
$currentUser = null;
$isAdmin = false;
$flashes = [];
$content = '';
ob_start();
require __DIR__ . '/../views/layouts/main.php';
$html = ob_get_clean();
check(substr_count($html, 'action="/auth/lang" method="POST"') === 2, 'Both language choices must submit POST forms');
check(substr_count($html, 'name="_csrf_token"') >= 2, 'Language forms must include CSRF tokens');
check(!str_contains($html, '/auth/lang?'), 'Language choices must not use GET links');

if ($failures) {
    fwrite(STDERR, implode("\n", $failures) . "\n");
    exit(1);
}
echo "Auth CSRF regression tests passed.\n";