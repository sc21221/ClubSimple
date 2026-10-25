<?php

declare(strict_types=1);

namespace Clubmanager\Repositories;

use Clubmanager\Models\MembershipFee;
use Clubmanager\Core\Database;

/**
 * Membership Fee Repository
 *
 * @package Clubmanager\Repositories
 * @author  Reinhard Schiessl
 */
class MembershipFeeRepository
{
    /**
     * Get summary metrics of the most recent SEPA debit run.
     *
     * @return array{date: string|null, total_amount: float, count: int}
     */
    public function getLastRunSummary(): array
    {
        // Find the latest due_date or created_at in membership_fees
        $sql = "SELECT created_at, due_date FROM `membership_fees` ORDER BY id DESC LIMIT 1";
        $latest = Database::fetchOne($sql);

        if (!$latest) {
            return [
                'date' => null,
                'due_date' => null,
                'total_amount' => 0.0,
                'count' => 0,
            ];
        }

        $createdAtDate = substr((string)$latest['created_at'], 0, 10);
        $dueDate = $latest['due_date'];

        // Aggregate all fees created on that latest date or with that due_date
        $aggSql = "SELECT SUM(amount) as total, COUNT(*) as cnt 
                   FROM `membership_fees` 
                   WHERE due_date = ? OR created_at LIKE ?";
        $res = Database::fetchOne($aggSql, [$dueDate, $createdAtDate . '%']);

        return [
            'date' => $createdAtDate,
            'due_date' => $dueDate,
            'total_amount' => (float) ($res['total'] ?? 0.0),
            'count' => (int) ($res['cnt'] ?? 0),
        ];
    }

    /**
     * Record a new fee debit.
     *
     * @param int $memberId
     * @param int $feeClassId
     * @param float $amount
     * @param string $dueDate
     * @param string $status
     * @return MembershipFee
     */
    public function recordDebit(
        int $memberId,
        int $feeClassId,
        float $amount,
        string $dueDate,
        string $status = 'Offen'
    ): MembershipFee {
        $fee = new MembershipFee([
            'member_id' => $memberId,
            'fee_class_id' => $feeClassId,
            'amount' => $amount,
            'due_date' => $dueDate,
            'paid_date' => null,
            'status' => $status,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $fee->save();
        return $fee;
    }

    /**
     * Get grouped SEPA run history.
     *
     * @param int $limit
     * @return array<int, array<string, mixed>>
     */
    public function getRunHistory(int $limit = 50): array
    {
        $sql = "SELECT due_date, 
                       MIN(created_at) as run_date, 
                       COUNT(DISTINCT member_id) as member_count, 
                       COUNT(*) as transaction_count, 
                       SUM(amount) as total_amount,
                       status
                FROM `membership_fees`
                GROUP BY due_date, status
                ORDER BY due_date DESC
                LIMIT {$limit}";
        return Database::fetchAll($sql);
    }

    /**
     * Get detailed fees for a specific member.
     *
     * @param int $memberId
     * @return array<int, array<string, mixed>>
     */
    public function getForMember(int $memberId): array
    {
        $sql = "SELECT mf.*, fc.name as fee_class_name
                FROM `membership_fees` mf
                JOIN `fee_classes` fc ON fc.id = mf.fee_class_id
                WHERE mf.member_id = ?
                ORDER BY mf.due_date DESC";
        return Database::fetchAll($sql, [$memberId]);
    }
}
