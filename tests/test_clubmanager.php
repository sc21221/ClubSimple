<?php

declare(strict_types=1);

// Setup clean test environment using isolated test database
$testDbFile = __DIR__ . '/../storage/test_database.sqlite';
if (file_exists($testDbFile)) {
    unlink($testDbFile);
}
putenv("DB_CONNECTION=sqlite");
putenv("DB_SQLITE_PATH=storage/test_database.sqlite");

require_once __DIR__ . '/../vendor/autoload.php';

use Clubmanager\Core\Database;
use Clubmanager\Core\Logger;
use Clubmanager\Core\Auth;
use Clubmanager\Models\Club;
use Clubmanager\Models\Member;
use Clubmanager\Models\FeeClass;
use Clubmanager\Models\User;
use Clubmanager\Models\AuditLog;
use Clubmanager\Repositories\ClubRepository;
use Clubmanager\Repositories\MemberRepository;
use Clubmanager\Repositories\FeeClassRepository;
use Clubmanager\Repositories\MembershipFeeRepository;
use Clubmanager\Services\SepaService;
use Clubmanager\Services\ReportService;
use Clubmanager\Services\GdprService;

echo "====================================================\n";
echo "Clubmanager Automated Verification & Test Suite\n";
echo "Version: 1.7 | Autor: Reinhard Schiessl\n";
echo "====================================================\n\n";

$passed = 0;
$failed = 0;

function assertTest(string $name, bool $condition) {
    global $passed, $failed;
    if ($condition) {
        echo "[PASS] " . $name . "\n";
        $passed++;
    } else {
        echo "[FAIL] " . $name . "\n";
        $failed++;
    }
}

// 1. Test Database connection & Initializer
try {
    $db = Database::getConnection();
    assertTest("Database connected via driver: " . Database::getDriver(), $db instanceof PDO);
} catch (\Exception $e) {
    assertTest("Database connection failed: " . $e->getMessage(), false);
}

// 2. Test Club Repository & Master Data
$clubRepo = new ClubRepository();
$club = $clubRepo->getPrimary();
assertTest("Club master record loaded: " . ($club->name ?? 'None'), $club instanceof Club && str_contains($club->name, 'Denkendorf'));
assertTest("Club representative (boss) is Reinhard Schiessl", $club->boss === 'Reinhard Schiessl');
assertTest("Club creditor ID is valid", !empty($club->bank_creditor_id) && strlen($club->bank_creditor_id) >= 10);
assertTest("Club IBAN and BIC are present", !empty($club->bank_iban) && !empty($club->bank_bic));

// 3. Test Users & Authentication
$adminUser = User::where('email = ?', ['admin@musikverein-denkendorf.de'])[0] ?? null;
assertTest("Admin user exists in database", $adminUser instanceof User);
assertTest("Admin user verifyPassword('admin') succeeds", $adminUser && $adminUser->verifyPassword('admin'));
assertTest("Admin user has isAdmin() === true", $adminUser && $adminUser->isAdmin());

$authAttempt = Auth::attempt('admin@musikverein-denkendorf.de', 'admin', 'de');
assertTest("Auth::attempt() with valid credentials returns true", $authAttempt === true);
assertTest("Auth::user() returns logged-in user", Auth::user() !== null && Auth::user()->email === 'admin@musikverein-denkendorf.de');
assertTest("Auth::isAdmin() returns true for admin", Auth::isAdmin() === true);

// 4. Test Fee Classes
$feeClassRepo = new FeeClassRepository();
$feeClasses = $feeClassRepo->getAll();
assertTest("Fee classes loaded (> 3 fee classes)", count($feeClasses) >= 4);

$youthClass = $feeClassRepo->findForAge(14);
assertTest("Fee class for age 14 resolved to Jugend", $youthClass !== null && str_contains($youthClass->name, 'Jugend'));

$adultClass = $feeClassRepo->findForAge(35);
assertTest("Fee class for age 35 resolved to Erwachsene", $adultClass !== null && str_contains($adultClass->name, 'Erwachsene'));

// 5. Test Members & Filtering
$memberRepo = new MemberRepository();
$activeCount = $memberRepo->getActiveCount();
assertTest("Active members count >= 5", $activeCount >= 5);

$searchResults = $memberRepo->search(['search' => 'Bauer']);
assertTest("Search for 'Bauer' finds member Lukas Bauer", count($searchResults) === 1 && $searchResults[0]->first_name === 'Lukas');

$member1 = $searchResults[0];
assertTest("Member 1 age is 18", $member1->getAge() >= 18);
assertTest("Member 1 direct debit eligibility is true", $member1->isDirectDebitEligible() === true);

// 6. Test Automated Age-Based Recategorization & SEPA
$sepaService = new SepaService();
$recats = $sepaService->detectAgeRecategorizations(date('Y-m-d'));
assertTest("Automated age recategorization detects Lukas Bauer (18yo in Jugend fee class)", count($recats) >= 1);
if (!empty($recats)) {
    echo "       Detected: " . $recats[0]['member']->getFullName() . " (Age " . $recats[0]['age'] . ") from '" . $recats[0]['current_fee_class']->name . "' to '" . $recats[0]['new_fee_class']->name . "'\n";
}

$preview = $sepaService->previewDebits();
assertTest("SEPA preview transactions count > 0", $preview['total_count'] > 0);
assertTest("SEPA preview total amount > 0.00", $preview['total_amount'] > 0.0);
echo "       SEPA Preview Total: " . format_money($preview['total_amount']) . " across " . $preview['total_count'] . " debits\n";

// Test SEPA XML generation
$xml = $sepaService->generateSepaXml($club, $preview['transactions'], date('Y-m-d', strtotime('+7 days')), 'Mitgliedsbeitrag 2025 Musikverein Denkendorf');
assertTest("SEPA XML contains <Document xmlns=\"urn:iso:std:iso:20022:tech:xsd:pain.008.001.02\"", str_contains($xml, 'urn:iso:std:iso:20022:tech:xsd:pain.008.001.02'));
assertTest("SEPA XML contains Creditor Scheme ID: " . $club->bank_creditor_id, str_contains($xml, $club->bank_creditor_id));
assertTest("SEPA XML contains DrctDbtTxInf transactions", str_contains($xml, '<DrctDbtTxInf>'));

// Test SEPA Run Execution
$runResult = $sepaService->executeDebitRun($club, date('Y-m-d', strtotime('+7 days')), 'Mitgliedsbeitrag 2025 Testlauf', true, (int)$adminUser->id);
assertTest("SEPA executeDebitRun created XML file on disk: " . $runResult['filename'], file_exists($runResult['filepath']));
assertTest("SEPA executeDebitRun recorded recategorized members: " . $runResult['recategorized_count'], $runResult['recategorized_count'] >= 1);

// Test Dashboard Last SEPA Summary
$feeRepo = new MembershipFeeRepository();
$lastSummary = $feeRepo->getLastRunSummary();
assertTest("Dashboard last SEPA run summary amount matches run: €" . $lastSummary['total_amount'], $lastSummary['total_amount'] > 0);

// 7. Test Reports (Birthdays & Anniversaries)
$reportService = new ReportService();
$birthdays = $reportService->getUpcomingBirthdays(365, true);
assertTest("Upcoming round birthdays found (e.g. 50, 70)", count($birthdays) >= 1);
if (!empty($birthdays)) {
    echo "       Round birthday: " . $birthdays[0]['full_name'] . " turning " . $birthdays[0]['turning_age'] . " in " . $birthdays[0]['days_until'] . " days\n";
}

$anniversaries = $reportService->getUpcomingAnniversaries((int)date('Y'), true);
assertTest("Upcoming anniversaries found (e.g. 25, 50 years)", count($anniversaries) >= 1);
if (!empty($anniversaries)) {
    echo "       Anniversary: " . $anniversaries[0]['full_name'] . " - " . $anniversaries[0]['years'] . " years of membership\n";
}

// 8. Test GDPR Service Dossier
$gdprService = new GdprService();
$dossier = $gdprService->getMemberDossier($member1);
assertTest("GDPR dossier contains personal_data, contact_data, bank_data", isset($dossier['personal_data'], $dossier['contact_data'], $dossier['bank_data']));

// 9. Test Audit Trail Log
$auditLogs = (new \Clubmanager\Repositories\AuditLogRepository())->search([], 10);
assertTest("Audit logs recorded in immutable ledger (> 0 logs)", count($auditLogs) > 0);
echo "       Latest Audit Event: " . $auditLogs[0]->action . " on " . $auditLogs[0]->entity_type . " #" . $auditLogs[0]->entity_id . "\n";

// 10. Test Structured Serilog Logger File Output
$logContent = file_get_contents(dirname(__DIR__) . '/storage/logs/clubmanager.log');
assertTest("Serilog structured log file contains valid JSON CLEF entries (@t, @mt)", str_contains($logContent, '"@t"') && str_contains($logContent, '"@mt"'));

echo "\n----------------------------------------------------\n";
echo "SUMMARY: {$passed} PASSED, {$failed} FAILED\n";
echo "====================================================\n";

if ($failed > 0) {
    exit(1);
}
