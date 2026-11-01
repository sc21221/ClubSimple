<?php

declare(strict_types=1);

$databasePath = 'storage/.test_model_schema_' . bin2hex(random_bytes(8)) . '.sqlite';
$absoluteDatabasePath = dirname(__DIR__) . '/' . $databasePath;
putenv('DB_CONNECTION=sqlite');
putenv('DB_SQLITE_PATH=' . $databasePath);

require_once __DIR__ . '/../vendor/autoload.php';

use Clubmanager\Core\Database;
use Clubmanager\Models\AuditLog;
use Clubmanager\Models\Club;
use Clubmanager\Models\FeeClass;
use Clubmanager\Models\Member;
use Clubmanager\Models\MemberFeeClass;
use Clubmanager\Models\MembershipFee;
use Clubmanager\Models\User;
use Clubmanager\Repositories\FeeClassRepository;
use Clubmanager\Repositories\MemberRepository;

$passed = 0;
$failed = 0;

function assertSchemaTest(string $name, bool $condition): void
{
    global $passed, $failed;

    if ($condition) {
        echo "[PASS] {$name}\n";
        $passed++;
    } else {
        echo "[FAIL] {$name}\n";
        $failed++;
    }
}

function rejectsSchemaTest(callable $operation): bool
{
    try {
        $operation();
        return false;
    } catch (Throwable) {
        return true;
    }
}

try {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SESSION['user_name'] = 'Schema Test Operator';

    $pdo = Database::getConnection();
    $feeClass = new FeeClass([
        'name' => 'Schema Test Fee Class',
        'base_amount' => 12.50,
    ]);
    $feeClassSaved = false;
    try {
        $feeClassSaved = $feeClass->save();
    } catch (Throwable) {
    }
    assertSchemaTest('Authenticated fee-class insert succeeds without an updated_by column', $feeClassSaved);
    assertSchemaTest(
        'Fee-class database defaults are loaded after insert',
        $feeClassSaved
            && $feeClass->currency === 'EUR'
            && $feeClass->billing_cycle === 'Jährlich'
            && (int) $feeClass->is_active === 1
            && $feeClass->created_at !== null
    );

    $testUser = new User([
        'name' => 'Schema Test User',
        'email' => 'schema-test-' . bin2hex(random_bytes(4)) . '@example.test',
        'password' => password_hash('test-password', PASSWORD_DEFAULT),
    ]);
    $userSaved = false;
    try {
        $userSaved = $testUser->save();
    } catch (Throwable) {
    }
    assertSchemaTest('Authenticated user insert does not write an unsupported updated_by column', $userSaved);

    $member = new Member([
        'first_name' => 'Schema',
        'last_name' => 'Consistency Test',
        'join_date' => '2024-01-01',
        'birth_date' => '2000-01-01',
    ]);
    $memberSaved = false;
    try {
        $memberSaved = $member->save();
    } catch (Throwable) {
    }
    assertSchemaTest('Authenticated member insert succeeds', $memberSaved);
    assertSchemaTest(
        'Member database defaults are loaded after insert',
        $memberSaved && $member->status === 'Aktiv' && $member->salutation === 'Herr'
    );

    $auditLog = new AuditLog([
        'action' => 'SCHEMA_TEST',
        'entity_type' => 'test',
        'entity_id' => 1,
    ]);
    $auditSaved = false;
    try {
        $auditSaved = $auditLog->save();
    } catch (Throwable) {
    }
    assertSchemaTest('Authenticated audit-log insert succeeds without updated_by', $auditSaved);

    $club = Club::find(1);
    $clubSaved = false;
    if ($club) {
        $club->name = 'Schema Test Club Name';
        try {
            $clubSaved = $club->save();
        } catch (Throwable) {
        }
    }
    assertSchemaTest('Club save records updated_by only on a supported table', $clubSaved && $club->updated_by === 'Schema Test Operator');

    $freshMember = $memberSaved ? Member::find($member->id) : null;
    $beforeTimestamp = $freshMember?->updated_at;
    if ($freshMember) {
        sleep(1);
        $freshMember->first_name = 'Dirty Update';
        try {
            $freshMember->save();
        } catch (Throwable) {
        }
    }
    assertSchemaTest(
        'SQLite updates updated_at when a model changes',
        $freshMember !== null && $beforeTimestamp !== $freshMember->updated_at
    );

    if ($freshMember && $feeClassSaved) {
        Database::execute('UPDATE `members` SET `city` = ? WHERE `id` = ?', ['Concurrent value', $freshMember->id]);
        $freshMember->last_name = 'Dirty Only';
        $dirtySaveSucceeded = false;
        try {
            $dirtySaveSucceeded = $freshMember->save();
        } catch (Throwable) {
        }
        $currentMember = Member::find($freshMember->id);
        assertSchemaTest(
            'Model update leaves unrelated database changes untouched',
            $dirtySaveSucceeded && $currentMember?->city === 'Concurrent value'
        );

        $assignment = new MemberFeeClass([
            'member_id' => $freshMember->id,
            'fee_class_id' => $feeClass->id,
            'start_date' => '2024-01-01',
        ]);
        $assignmentSaved = false;
        try {
            $assignmentSaved = $assignment->save();
        } catch (Throwable) {
        }
        assertSchemaTest('Member-fee assignment model saves while authenticated', $assignmentSaved);

        $membershipFee = new MembershipFee([
            'member_id' => $freshMember->id,
            'fee_class_id' => $feeClass->id,
            'amount' => 12.50,
            'due_date' => '2024-02-01',
        ]);
        $membershipFeeSaved = false;
        try {
            $membershipFeeSaved = $membershipFee->save();
        } catch (Throwable) {
        }
        assertSchemaTest('Membership-fee model saves while authenticated', $membershipFeeSaved);

        assertSchemaTest(
            'Duplicate member-fee assignments are rejected',
            rejectsSchemaTest(static function () use ($freshMember, $feeClass): void {
                $duplicate = new MemberFeeClass([
                    'member_id' => $freshMember->id,
                    'fee_class_id' => $feeClass->id,
                    'start_date' => '2024-01-01',
                ]);
                $duplicate->save();
            })
        );
        assertSchemaTest(
            'Invalid enum values are rejected by SQLite',
            rejectsSchemaTest(static function () use ($pdo): void {
                $pdo->exec("INSERT INTO `fee_classes` (`name`, `base_amount`, `billing_cycle`) VALUES ('Invalid Cycle', 1, 'Weekly')");
            })
        );
        assertSchemaTest(
            'Orphaned fee records are rejected by SQLite',
            rejectsSchemaTest(static function () use ($pdo, $feeClass): void {
                $statement = $pdo->prepare("INSERT INTO `membership_fees` (`member_id`, `fee_class_id`, `amount`, `due_date`) VALUES (?, ?, 1, '2024-02-01')");
                $statement->execute([99999999, $feeClass->id]);
            })
        );

        $memberDeleteBlocked = !(new MemberRepository())->delete($freshMember);
        assertSchemaTest('Member deletion preserves linked payment history and assignments', $memberDeleteBlocked);
        $feeClassDeleteBlocked = !(new FeeClassRepository())->delete($feeClass);
        assertSchemaTest('Fee-class deletion preserves linked payment history and assignments', $feeClassDeleteBlocked);
    } else {
        assertSchemaTest('Remaining relation, constraint, and deletion checks have fixtures', false);
    }
} finally {
    unset($_SESSION['user_name']);
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    if (file_exists($absoluteDatabasePath)) {
        unlink($absoluteDatabasePath);
    }
}

echo "SUMMARY: {$passed} PASSED, {$failed} FAILED\n";
exit($failed === 0 ? 0 : 1);