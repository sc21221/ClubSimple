<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Clubmanager\Controllers\MemberController;
use Clubmanager\Core\Csrf;
use Clubmanager\Core\Database;
use Clubmanager\Core\Request;

class AssignmentTestController extends MemberController
{
    public string $location = '';
    public array $rendered = [];

    public function redirect(string $url, int $status = 302): void { $this->location = $url; }
    public function render(string $view, array $data = [], ?string $layout = 'main'): void { $this->rendered = $data; }
}

session_start();
$db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$db->exec('PRAGMA foreign_keys = ON');
$db->exec('CREATE TABLE members (id INTEGER PRIMARY KEY)');
$db->exec('CREATE TABLE fee_classes (id INTEGER PRIMARY KEY, name TEXT, base_amount REAL, currency TEXT, billing_cycle TEXT, min_age INTEGER, max_age INTEGER, is_active INTEGER)');
$db->exec('CREATE TABLE member_fee_classes (id INTEGER PRIMARY KEY, member_id INTEGER REFERENCES members(id), fee_class_id INTEGER REFERENCES fee_classes(id), custom_amount REAL, start_date TEXT NOT NULL, end_date TEXT, is_active INTEGER, created_at TEXT, UNIQUE(member_id, fee_class_id, start_date))');
$db->exec('CREATE TABLE audit_logs (id INTEGER PRIMARY KEY, timestamp TEXT, user_id INTEGER, action TEXT, entity_type TEXT, entity_id INTEGER, old_values TEXT, new_values TEXT, ip_address TEXT)');
$db->exec("INSERT INTO members VALUES (1), (2)");
$db->exec("INSERT INTO fee_classes VALUES (1, 'A', 10, 'EUR', 'Jährlich', 0, 99, 1), (2, 'B', 20, 'EUR', 'Jährlich', 0, 99, 1), (3, 'C', 30, 'EUR', 'Jährlich', 0, 99, 0)");
$db->exec("INSERT INTO member_fee_classes VALUES (10, 1, 1, 12.5, '2024-01-01', '2025-01-01', 1, '2024-01-01'), (11, 1, 2, NULL, '2024-01-01', NULL, 0, '2024-01-01'), (12, 2, 1, 7, '2024-01-01', NULL, 1, '2024-01-01')");
(new ReflectionProperty(Database::class, 'instance'))->setValue(null, $db);
$controller = new AssignmentTestController();
$token = Csrf::getToken();
$snapshot = static fn(): array => $db->query('SELECT * FROM member_fee_classes ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$check = static function (bool $condition, string $message): void {
    if (!$condition) { throw new RuntimeException($message); }
    echo "[PASS] $message\n";
};
$request = static function (mixed $fee, string $csrf) use ($controller): void {
    $_POST = ['_csrf_token' => $csrf, 'fee_class_id' => $fee, 'custom_amount' => 999, 'start_date' => '2030-01-01', 'end_date' => '', 'is_active' => 0];
    $controller->updateFeeClass(new Request(), 1, 10);
};

$route = file_get_contents(__DIR__ . '/../public/index.php');
$check(str_contains($route, "'/members/{id}/fee/{assignmentId}/update', [\\Clubmanager\\Controllers\\MemberController::class, 'updateFeeClass'], ['auth']"), 'Authenticated update route registered');
$controller->edit(new Request(), 1);
$check(count($controller->rendered['assignedFees']) === 1 && count($controller->rendered['feeClasses']) === 3, 'Edit supplies active assignments and all fee classes');
$before = $snapshot();
foreach (['', '2abc', '0', '999', [], null] as $invalid) {
    $request($invalid, $token);
    $check($snapshot() === $before, 'Invalid fee class rejected: ' . json_encode($invalid));
}
$request('3', 'invalid');
$check($snapshot() === $before, 'Invalid CSRF rejected');
$request('2', $token);
$check($snapshot() === $before, 'Conflicting inactive assignment rejected');
$_POST = ['_csrf_token' => $token, 'fee_class_id' => '3'];
$controller->updateFeeClass(new Request(), 1, 12);
$controller->updateFeeClass(new Request(), 1, 999);
$controller->updateFeeClass(new Request(), 999, 10);
$check($snapshot() === $before, 'Foreign or missing assignment and missing member rejected');
$request('3', $token);
$after = $snapshot();
$expected = $before;
$expected[0]['fee_class_id'] = 3;
$check($after === $expected, 'Only selected fee class changes; inactive fee class is selectable');
$check($controller->location === '/members/1/edit', 'Update redirects to member edit');
$request('3', $token);
$check($snapshot() === $after, 'Selecting current class is a harmless no-op');