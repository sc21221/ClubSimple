<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Clubmanager\Controllers\UserController;
use Clubmanager\Core\Csrf;
use Clubmanager\Core\Database;
use Clubmanager\Core\Request;
use Clubmanager\Models\User;

class RoleTestController extends UserController
{
    public string $location = '';
    public function redirect(string $url, int $status = 302): void { $this->location = $url; }
}

session_start();
$db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$db->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT, password TEXT, phone TEXT, admin INTEGER, role TEXT, locale TEXT, club_id INTEGER, created_at TEXT, updated_at TEXT)');
$db->exec('CREATE TABLE audit_logs (id INTEGER PRIMARY KEY, timestamp TEXT, user_id INTEGER, action TEXT, entity_type TEXT, entity_id INTEGER, old_values TEXT, new_values TEXT, ip_address TEXT)');
(new ReflectionProperty(Database::class, 'instance'))->setValue(null, $db);
$controller = new RoleTestController();
$token = Csrf::getToken();
$check = static function (bool $ok, string $message): void {
    if (!$ok) { throw new RuntimeException($message); }
    echo "[PASS] $message\n";
};
$translations = ['de' => require __DIR__ . '/../lang/de.php', 'en' => require __DIR__ . '/../lang/en.php'];
foreach (['board', 'conductor'] as $role) {
    $check(in_array($role, User::ROLES, true), "$role is available");
    foreach ($translations as $locale => $labels) {
        $check(!empty($labels['role_' . $role]), "$role has a $locale label");
    }
}
foreach (User::ROLES as $role) {
    $_POST = ['_csrf_token' => $token, 'name' => 'Test', 'email' => $role . '@example.org', 'password' => 'password', 'role' => $role, 'admin' => 1];
    $controller->store(new Request());
    $user = User::where('email = ?', [$role . '@example.org'])[0];
    $check($user->getRole() === $role && $user->isAdmin() === ($role === 'admin'), "Create $role with correct privileges");
    $html = (static function (User $user): string {
        ob_start();
        require __DIR__ . '/../views/users/edit.php';
        return ob_get_clean();
    })($user);
    $check(str_contains($html, 'value="' . $role . '" selected') && substr_count($html, '<option value="') >= 6, "Edit selects $role");
}
$user = User::where('email = ?', ['admin@example.org'])[0];
foreach (['board', 'conductor'] as $role) {
    $_POST = ['_csrf_token' => $token, 'name' => 'Test', 'email' => $user->email, 'role' => $role];
    $controller->update(new Request(), $user->id);
    $check(User::find($user->id)->getRole() === $role && !User::find($user->id)->isAdmin(), "Update to $role without admin privileges");
}
$_POST = ['_csrf_token' => $token, 'name' => 'Test', 'email' => $user->email, 'role' => 'secretary', 'admin' => 1];
$controller->update(new Request(), $user->id);
$check(!User::find($user->id)->isAdmin() && User::find($user->id)->getRole() === 'secretary', 'Demotion removes admin rights');
foreach (['invalid', '', ['admin'], null] as $invalid) {
    $_POST['role'] = $invalid;
    $controller->update(new Request(), $user->id);
    $check(User::find($user->id)->getRole() === 'secretary' && $controller->location === '/users/' . $user->id . '/edit', 'Invalid role rejected');
}
$_POST['role'] = 'admin';
$_POST['_csrf_token'] = 'invalid';
$controller->update(new Request(), $user->id);
$check(!User::find($user->id)->isAdmin(), 'Invalid CSRF rejected');
$_POST['_csrf_token'] = $token;
$controller->update(new Request(), $user->id);
$check(User::find($user->id)->isAdmin(), 'Promotion grants admin rights');