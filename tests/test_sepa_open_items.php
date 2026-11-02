<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Clubmanager\Controllers\SepaController;
use Clubmanager\Core\Csrf;
use Clubmanager\Core\Database;
use Clubmanager\Core\Request;

class OpenItemsTestController extends SepaController
{
    public string $location = '';
    public array $rendered = [];
    public function redirect(string $url, int $status = 302): void { $this->location = $url; }
    public function render(string $view, array $data = [], ?string $layout = 'main'): void { $this->rendered = [$view, $data]; }
}

$check = static function (bool $ok, string $message): void { if (!$ok) { throw new RuntimeException($message); } };
$routes = file_get_contents(__DIR__ . '/../public/index.php');
$check(str_contains($routes, "'/sepa/open', [SepaController::class, 'open'], ['auth']") && str_contains($routes, "'/sepa/open/paid', [SepaController::class, 'markOpenPaid'], ['auth']"), 'Authenticated routes missing');
$check(is_file(__DIR__ . '/../views/sepa/open.php'), 'View missing');
session_start();
$db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$db->exec('CREATE TABLE members (id INTEGER PRIMARY KEY, first_name TEXT, last_name TEXT)');
$db->exec('CREATE TABLE fee_classes (id INTEGER PRIMARY KEY, name TEXT)');
$db->exec('CREATE TABLE sepa_runs (id INTEGER PRIMARY KEY, due_date TEXT, created_at TEXT)');
$db->exec('CREATE TABLE membership_fees (id INTEGER PRIMARY KEY, member_id INTEGER, fee_class_id INTEGER, amount DECIMAL, due_date TEXT, paid_date TEXT, status TEXT, created_at TEXT, sepa_run_id INTEGER)');
$db->exec("INSERT INTO sepa_runs VALUES (1,'2025-01-01','2024-01-01'), (2,'2025-01-01','2024-01-01'), (3,'2025-01-01','2024-01-01')");
$db->exec("INSERT INTO members VALUES (1, 'Anna', 'Beispiel')");
$db->exec("INSERT INTO fee_classes VALUES (1, 'Beitrag'), (2, 'Abteilung')");
$db->exec("INSERT INTO membership_fees VALUES (1,1,1,10,'2025-01-01',NULL,'Offen','2024-01-01',1), (2,1,2,20,'2025-01-01',NULL,'Überfällig','2024-01-01',2), (3,1,1,30,'2025-01-01','2025-01-02','Bezahlt','2024-01-01',3)");
(new ReflectionProperty(Database::class, 'instance'))->setValue(null, $db);
$controller = new OpenItemsTestController();
$snapshot = static fn(): array => $db->query('SELECT * FROM membership_fees ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$controller->open(new Request());
$check($controller->rendered[0] === 'sepa/open' && array_map('intval', array_column($controller->rendered[1]['items'], 'id')) === [1, 2], 'Open items');
$repo = new \Clubmanager\Repositories\MembershipFeeRepository();
$history = $repo->getRunHistory();
$check(count($history) === 3 && (int) $repo->getLastRunSummary()['count'] === 1, 'Same-date runs remain separate');
$token = Csrf::getToken();
$before = $snapshot();
$_POST = ['_csrf_token' => 'invalid'];
$controller->deleteRun(new Request(), '1');
$check($snapshot() === $before, 'Deletion requires CSRF');
$_POST = ['_csrf_token' => $token];
$controller->deleteRun(new Request(), '3');
$check($snapshot() === $before && count($repo->getRuns()) === 3, 'Paid run cannot be deleted');
foreach ([[], ['1', '3'], ['1', '999'], ['1', '1'], ['1x'], ['0'], [['1']]] as $ids) {
    $_POST = ['_csrf_token' => $token, 'ids' => $ids];
    $controller->markOpenPaid(new Request());
    $check($snapshot() === $before, 'Invalid or stale selection must not modify any item');
}
$_POST = ['_csrf_token' => 'invalid', 'ids' => ['1']];
$controller->markOpenPaid(new Request());
$check($snapshot() === $before, 'CSRF rejected');
$_POST = ['_csrf_token' => $token, 'ids' => ['1', '2']];
$controller->markOpenPaid(new Request());
$after = $snapshot();
foreach ([0, 1] as $i) {
    $check($after[$i]['status'] === 'Bezahlt' && $after[$i]['paid_date'] === date('Y-m-d'), 'Selected item paid today');
}
$check($after[2] === $before[2] && $controller->location === '/sepa/open', 'Other items unchanged and redirect');
$db->exec("UPDATE membership_fees SET status='Offen', paid_date=NULL WHERE id=1");
$controller->deleteRun(new Request(), '1');
$check(count($repo->getRuns()) === 2 && count($snapshot()) === 2, 'Only selected open run and its fees deleted');
echo "SEPA open items tests passed.\n";