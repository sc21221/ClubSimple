<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Clubmanager\Models\FeeClass;
use Clubmanager\Models\Member;

session_start();
$member = new Member(['id' => 42, 'first_name' => 'Anna', 'last_name' => 'Test', 'birth_date' => '2000-01-01'], true);
$feeClasses = [new FeeClass(['id' => 3, 'name' => 'Jugend'], true), new FeeClass(['id' => 4, 'name' => 'Erwachsene'], true)];
$assignedFees = [
    ['id' => 9, 'fee_class_id' => 3, 'start_date' => '2024-01-01', 'end_date' => null, 'custom_amount' => null],
    ['id' => 10, 'fee_class_id' => 4, 'start_date' => '2025-01-01', 'end_date' => null, 'custom_amount' => '20.00'],
];
ob_start();
require __DIR__ . '/../views/members/edit.php';
$html = ob_get_clean();
$checks = [
    'Each assignment has its own form' => str_contains($html, '/members/42/fee/9/update') && str_contains($html, '/members/42/fee/10/update'),
    'Current classes are selected' => str_contains($html, 'value="3" selected') && str_contains($html, 'value="4" selected'),
    'Each form has a CSRF token' => substr_count($html, 'name="_csrf_token"') === 3,
    'Custom amount remains visible' => str_contains($html, '20,00'),
    'Five tabs are rendered' => substr_count($html, 'role="tab"') === 5 && substr_count($html, 'role="tabpanel"') === 5,
    'Member and fee forms remain separate' => str_contains($html, 'id="member-edit-form" class="tab-content"') && substr_count($html, '<form ') === 3,
    'Invalid fields activate their tab' => str_contains($html, "addEventListener('invalid'") && str_contains($html, 'bootstrap.Tab.getOrCreateInstance'),
];
$assignedFees = [];
ob_start();
require __DIR__ . '/../views/members/edit.php';
$emptyHtml = ob_get_clean();
$checks['Unassigned member can reach fee management'] = str_contains($emptyHtml, 'href="/members/42"');
foreach ($checks as $name => $passed) {
    echo ($passed ? '[PASS] ' : '[FAIL] ') . $name . "\n";
}
exit(in_array(false, $checks, true) ? 1 : 0);