<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Clubmanager\Core\Database;
use Clubmanager\Database\DatabaseInitializer;
use Clubmanager\Models\Club;
use Clubmanager\Services\SepaService;

$check = static function (bool $ok, string $message): void { if (!$ok) { throw new RuntimeException($message); } };
$db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
DatabaseInitializer::initialize($db);
(new ReflectionProperty(Database::class, 'instance'))->setValue(null, $db);
$db->exec("INSERT INTO membership_fees (member_id, fee_class_id, amount, due_date) VALUES (1, 1, 5, '2020-01-01')");
$db->exec("INSERT INTO member_fee_classes (member_id, fee_class_id, custom_amount, start_date) VALUES (1, 2, 12.50, '2020-01-01')");
$service = new SepaService();
$club = Club::find(1);
$preview = $service->previewDebits();
$dueDate = random_int(3000, 9999) . '-12-30';
$prefix = __DIR__ . '/../storage/sepa/SEPA_DirectDebit_' . str_replace('-', '', $dueDate) . '_';
$check(glob($prefix . '*.xml') === [], 'Test date already has files');
try {
    $first = $service->executeDebitRun($club, $dueDate, 'Beitrag', false);
    $second = $service->executeDebitRun($club, $dueDate, 'Beitrag', false);
    $check($first['filename'] !== $second['filename'] && is_file($first['filepath']) && is_file($second['filepath']), 'Each run retains a distinct XML file');
    $check($first['total_transactions'] === $preview['total_count'] && $second['total_amount'] === $preview['total_amount'] && str_contains($first['xml'], 'CstmrDrctDbtInitn'), 'Existing result contract');
    $runs = $db->query('SELECT * FROM sepa_runs ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    $check(count($runs) === 2 && $runs[0]['id'] !== $runs[1]['id'], 'Each execution needs a distinct run');
    foreach ($runs as $run) {
        $check($run['due_date'] === $dueDate && !empty($run['created_at']), 'Run metadata');
        $stmt = $db->prepare('SELECT * FROM membership_fees WHERE sepa_run_id = ?');
        $stmt->execute([$run['id']]);
        $fees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $expected = array_sum(array_map(static fn(array $tx): int => count($tx['fee_classes']), $preview['transactions']));
        $check(count($fees) === $expected, 'All fee classes must be linked to the run');
        $check(abs(array_sum(array_column($fees, 'amount')) - $preview['total_amount']) < 0.001, 'Fee amounts');
        foreach ($fees as $fee) {
            $check($fee['status'] === 'Offen' && $fee['due_date'] === $dueDate, 'Fee status and due date');
        }
    }
    $check($db->query('SELECT sepa_run_id FROM membership_fees WHERE id = 1')->fetchColumn() === null, 'Legacy fee stays unlinked');
    $db->exec("CREATE TRIGGER fail_fee BEFORE INSERT ON membership_fees WHEN NEW.sepa_run_id IS NOT NULL AND (SELECT COUNT(*) FROM membership_fees WHERE sepa_run_id = NEW.sepa_run_id) = 1 BEGIN SELECT RAISE(ABORT, 'test failure'); END");
    try {
        $service->executeDebitRun($club, $dueDate, 'Beitrag', false);
        throw new RuntimeException('Expected insertion failure');
    } catch (PDOException $e) {
        $check(str_contains($e->getMessage(), 'test failure'), 'Unexpected failure');
    }
    $check((int) $db->query('SELECT COUNT(*) FROM sepa_runs')->fetchColumn() === 2, 'Failed run rolled back');
    $check((int) $db->query('SELECT COUNT(*) FROM membership_fees')->fetchColumn() === 1 + 2 * $expected, 'Failed fees rolled back');
    $db->exec("UPDATE members SET status = 'Inaktiv'");
    try {
        $service->executeDebitRun($club, $dueDate, 'Beitrag', false);
        throw new RuntimeException('Expected empty run failure');
    } catch (RuntimeException $e) {
        $check(str_contains($e->getMessage(), 'Keine abrechenbaren Mitglieder'), 'Unexpected empty run error');
    }
    $check((int) $db->query('SELECT COUNT(*) FROM sepa_runs')->fetchColumn() === 2, 'Empty run must not be stored');
} finally {
    foreach (glob($prefix . '*.xml') as $path) {
        if (is_file($path)) { unlink($path); }
    }
}

$legacy = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$legacy->exec('PRAGMA foreign_keys = ON; CREATE TABLE membership_fees (id INTEGER PRIMARY KEY); INSERT INTO membership_fees VALUES (1)');
$legacy->exec(file_get_contents(__DIR__ . '/../database/migrate_sepa_runs_sqlite.sql'));
$check($legacy->query('SELECT sepa_run_id FROM membership_fees')->fetchColumn() === null, 'Migration preserves legacy rows');
try {
    $legacy->exec('UPDATE membership_fees SET sepa_run_id = 999 WHERE id = 1');
    throw new RuntimeException('Foreign key must reject unknown run');
} catch (PDOException $e) {
    $check(str_contains($e->getMessage(), 'FOREIGN KEY'), 'Unexpected foreign key error');
}
echo "SEPA run storage tests passed.\n";