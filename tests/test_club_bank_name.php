<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Clubmanager\Core\Validator;

$schema = file_get_contents(__DIR__ . '/../database/schema.sql');
$initializer = file_get_contents(__DIR__ . '/../database/DatabaseInitializer.php');
$controller = file_get_contents(__DIR__ . '/../src/Controllers/ClubController.php');
$view = file_get_contents(__DIR__ . '/../views/club/edit.php');
$migration = @file_get_contents(__DIR__ . '/../database/migrate_club_bank_name.sql');

$checks = [
    'MySQL schema' => preg_match('/`bank_name`\s+varchar\(100\)/i', $schema) === 1,
    'SQLite schema' => preg_match('/`bank_name`\s+varchar\(100\)/i', $initializer) === 1,
    'Controller validation' => str_contains($controller, "'bank_name' => 'max:100'"),
    'Form limit' => preg_match('/name="bank_name"[^>]*maxlength="100"/', $view) === 1,
    'MySQL migration' => $migration !== false && preg_match('/ALTER TABLE\s+`clubs`\s+MODIFY\s+(?:COLUMN\s+)?`bank_name`\s+VARCHAR\(100\)/i', $migration) === 1,
    '100 characters accepted' => !Validator::make(['bank_name' => str_repeat('ä', 100)], ['bank_name' => 'max:100'])->fails(),
    '101 characters rejected' => Validator::make(['bank_name' => str_repeat('ä', 101)], ['bank_name' => 'max:100'])->fails(),
];

foreach ($checks as $name => $passed) {
    echo ($passed ? '[PASS] ' : '[FAIL] ') . $name . "\n";
}

exit(in_array(false, $checks, true) ? 1 : 0);