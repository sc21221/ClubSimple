<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Clubmanager\Controllers\UserController;
use Clubmanager\Core\Csrf;
use Clubmanager\Core\Database;
use Clubmanager\Core\Request;
use Clubmanager\Models\User;

class PasswordTestController extends UserController
{
    public string $location = '';
    public function redirect(string $url, int $status = 302): void { $this->location = $url; }
}

session_start();
$db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$db->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT, password TEXT, phone TEXT, admin INTEGER, role TEXT, locale TEXT, club_id INTEGER, created_at TEXT, updated_at TEXT)');
$db->exec('CREATE TABLE audit_logs (id INTEGER PRIMARY KEY, timestamp TEXT, user_id INTEGER, action TEXT, entity_type TEXT, entity_id INTEGER, old_values TEXT, new_values TEXT, ip_address TEXT)');
(new ReflectionProperty(Database::class, 'instance'))->setValue(null, $db);
$controller = new PasswordTestController();
$token = Csrf::getToken();
$check = static function (bool $ok, string $message): void {
    if (!$ok) { throw new RuntimeException($message); }
    echo "[PASS] $message\n";
};
foreach (['abcdefg', '1234567'] as $password) {
    $_POST = ['_csrf_token' => $token, 'name' => 'Test', 'email' => 'test@example.org', 'password' => $password, 'role' => 'user'];
    $controller->store(new Request());
    $check(count(User::where('email = ?', ['test@example.org'])) === 0, 'Short password rejected on create');
}
$_POST['password'] = '12345678';
$controller->store(new Request());
$user = User::where('email = ?', ['test@example.org'])[0];
$check(password_verify('12345678', $user->password), 'Eight digit password accepted on create');
foreach (['abcdefg', '1234567'] as $password) {
    $_POST['password'] = $password;
    $controller->update(new Request(), $user->id);
    $check(password_verify('12345678', User::find($user->id)->password), 'Short password rejected on update');
}
$_POST['password'] = '';
$controller->update(new Request(), $user->id);
$check(password_verify('12345678', User::find($user->id)->password), 'Empty password preserves existing password');
$_POST['password'] = 'abcdefgh';
$controller->update(new Request(), $user->id);
$check(password_verify('abcdefgh', User::find($user->id)->password), 'Eight character password accepted on update');
foreach (['create', 'edit'] as $view) {
    $html = file_get_contents(__DIR__ . '/../views/users/' . $view . '.php');
    $check(str_contains($html, 'minlength="8"'), "$view form specifies minimum length");
}