<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Clubmanager\Core\Database;
use Clubmanager\Repositories\FeeClassRepository;

$db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$db->exec('CREATE TABLE fee_classes (id INTEGER PRIMARY KEY, name TEXT, base_amount REAL, billing_cycle TEXT, min_age INTEGER, max_age INTEGER, is_active INTEGER)');
$db->exec('CREATE TABLE members (id INTEGER PRIMARY KEY, status TEXT)');
$db->exec('CREATE TABLE member_fee_classes (member_id INTEGER, fee_class_id INTEGER, is_active INTEGER)');
$db->exec("INSERT INTO fee_classes VALUES (1,'Zeta',10,'year',10,20,1), (2,'Alpha',30,'month',0,99,0), (3,'Beta',20,'year',10,15,1)");
$db->exec("INSERT INTO members VALUES (1,'Aktiv'), (2,'Aktiv'), (3,'Inaktiv')");
$db->exec('INSERT INTO member_fee_classes VALUES (1,1,1), (2,1,1), (3,2,1), (1,2,0), (1,3,1)');
(new ReflectionProperty(Database::class, 'instance'))->setValue(null, $db);
$repo = new FeeClassRepository();
$ids = static fn(array $classes): array => array_map(static fn($class) => (int) $class->id, $classes);
$check = static function (bool $ok, string $message): void {
    if (!$ok) { throw new RuntimeException($message); }
    echo "[PASS] $message\n";
};
$check($ids($repo->getAll()) === [2, 3, 1], 'Default name ordering');
$check($ids($repo->getAll('name', 'desc')) === [1, 3, 2], 'Descending names');
$check($ids($repo->getAll('base_amount', 'desc')) === [2, 3, 1], 'Numeric amounts');
$check($ids($repo->getAll('billing_cycle')) === [2, 3, 1], 'Billing cycle with stable ties');
$check($ids($repo->getAll('age_span')) === [2, 3, 1], 'Age range includes upper bound');
$check($ids($repo->getAll('members_count', 'desc')) === [1, 3, 2], 'Counts match active member assignments');
$check($ids($repo->getAll('status')) === [2, 3, 1], 'Status ordering');
$check($ids($repo->getAll('id DESC; DROP TABLE members', 'invalid')) === [2, 3, 1], 'Invalid parameters fall back safely');
$feeClasses = [];
$sort = 'base_amount';
$direction = 'asc';
ob_start();
require __DIR__ . '/../views/fee_classes/index.php';
$html = ob_get_clean();
$check(str_contains($html, 'sort=base_amount&amp;direction=desc'), 'Sort link toggles direction');
$check(str_contains($html, 'aria-sort="ascending"'), 'Current sort is accessible');