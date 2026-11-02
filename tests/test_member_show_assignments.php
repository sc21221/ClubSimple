<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$member = new class {
    public $id = 1;
    public $status = 'Aktiv';
    public $membership_type = '';
    public $salutation = '';
    public $sex = '';
    public $birth_date = '2000-01-01';
    public $join_date = '2020-01-01';
    public $leave_date = null;
    public $email = '';
    public $phone = '';
    public $street = '';
    public $zip = '';
    public $city = '';
    public $bank_holder = '';
    public $bank_name = '';
    public $bank_iban = '';
    public $bank_bic = '';
    public function getFullName() { return 'Test'; }
    public function getYearsOfMembership() { return 6; }
    public function getAge() { return 26; }
    public function isDirectDebitEligible() { return false; }
    public function getTotalActiveFeeAmount() { return 0; }
};
$assignedFees = $allFeeClasses = $feeHistory = $auditLogs = [];
$assignedDepartments = [(object) ['name' => '<Abteilung>', 'assigned_date' => null, 'description' => '<Hinweis>']];
$assignedFunctions = [(object) ['name' => 'Kassier', 'start_date' => '2020-01-02', 'end_date' => '2021-02-03', 'description' => null]];
$assignedEvents = [(object) ['name' => 'Jubiläum', 'event_date' => '2022-03-04', 'description' => 'Auszeichnung']];
$render = static function () use (&$member, &$assignedFees, &$allFeeClasses, &$feeHistory, &$auditLogs, &$assignedDepartments, &$assignedFunctions, &$assignedEvents): string {
    ob_start();
    require __DIR__ . '/../views/members/show.php';
    return ob_get_clean();
};
$html = $render();
foreach (['&lt;Abteilung&gt;', '&lt;Hinweis&gt;', 'Kassier', '02.01.2020', '03.02.2021', 'Jubiläum', '04.03.2022', 'Auszeichnung'] as $expected) {
    if (!str_contains($html, $expected)) { throw new RuntimeException("Missing: $expected"); }
}
$assignedDepartments = $assignedFunctions = $assignedEvents = [];
$html = $render();
foreach (['department_no_assignments', 'function_no_assignments', 'event_no_assignments'] as $key) {
    if (!str_contains($html, __($key))) { throw new RuntimeException("Missing empty state: $key"); }
}
echo "Member assignment details rendered successfully.\n";