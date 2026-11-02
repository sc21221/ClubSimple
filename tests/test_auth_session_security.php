<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Clubmanager\Core\Auth;
use Clubmanager\Core\Database;
use Clubmanager\Core\Session;
use Clubmanager\Models\User;

$db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$db->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT, password TEXT, admin INTEGER, role TEXT, locale TEXT, remember_token TEXT, created_at TEXT, updated_at TEXT)');
$stmt = $db->prepare('INSERT INTO users (id, name, email, password, admin, role, locale, remember_token) VALUES (1, ?, ?, ?, 0, ?, ?, ?)');
$stmt->execute(['Test', 'test@example.org', password_hash('password123', PASSWORD_DEFAULT), 'user', 'de', 'test-token']);
(new ReflectionProperty(Database::class, 'instance'))->setValue(null, $db);
Session::start();
$oldId = session_id();
if (!Auth::attempt('test@example.org', 'password123') || session_id() === $oldId || !Auth::check()) {
    throw new RuntimeException('Password login must renew the session ID and authenticate');
}
$db->exec('DELETE FROM users WHERE id = 1');
if (Auth::check() || Auth::user() !== null) {
    throw new RuntimeException('Deleted user must not remain authenticated');
}
$stmt->execute(['Test', 'test@example.org', password_hash('password123', PASSWORD_DEFAULT), 'user', 'de', 'test-token']);
$oldId = session_id();
Auth::login(User::find(1));
if (session_id() === $oldId || !Auth::check()) {
    throw new RuntimeException('Remember-me login must renew the session ID and authenticate');
}
echo "Session security tests passed.\n";