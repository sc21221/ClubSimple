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
    /** @return array<int, array<string, mixed>> */
    public function getOpenItems(): array
    {
        return Database::fetchAll("SELECT mf.*, m.first_name, m.last_name, fc.name AS fee_class_name
            FROM `membership_fees` mf
            JOIN `members` m ON m.id = mf.member_id
            JOIN `fee_classes` fc ON fc.id = mf.fee_class_id
            WHERE mf.status IN ('Offen', 'Überfällig') AND mf.paid_date IS NULL
            ORDER BY mf.due_date, mf.id");
    }

    /** @param int[] $ids */
    public function markOpenItemsPaid(array $ids): void
    {
        if ($ids === [] || count($ids) !== count(array_unique($ids))) {
            throw new \InvalidArgumentException('Ungültige Auswahl.');
        }
        foreach ($ids as $id) {
            if (!is_int($id) || $id <= 0) {
                throw new \InvalidArgumentException('Ungültige Auswahl.');
            }
        }

        $db = Database::getConnection();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("UPDATE `membership_fees` SET status = 'Bezahlt', paid_date = ?
                WHERE id = ? AND status IN ('Offen', 'Überfällig') AND paid_date IS NULL");
            $today = date('Y-m-d');
            foreach ($ids as $id) {
                $stmt->execute([$today, $id]);
                if ($stmt->rowCount() !== 1) {
                    throw new \InvalidArgumentException('Mindestens ein Posten ist nicht mehr offen.');
                }
            }
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function getRuns(): array
    {
        return Database::fetchAll('SELECT id, created_at, due_date FROM `sepa_runs` ORDER BY id DESC');
    }

    public function deleteRun(int $id): void
    {
        $db = Database::getConnection();
        $db->beginTransaction();
        try {
            $lock = $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
            $stmt = $db->prepare('SELECT id FROM `sepa_runs` WHERE id = ?' . $lock);
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                throw new \InvalidArgumentException('Der SEPA-Lauf wurde nicht gefunden.');
            }
            $stmt = $db->prepare('SELECT status, paid_date FROM `membership_fees` WHERE sepa_run_id = ?' . $lock);
            $stmt->execute([$id]);
            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $fee) {
                if ($fee['paid_date'] !== null || !in_array($fee['status'], ['Offen', 'Überfällig'], true)) {
                    throw new \InvalidArgumentException('Ein Lauf mit bereits verbuchten Posten kann nicht gelöscht werden.');
                }
            }
            $stmt = $db->prepare('DELETE FROM `membership_fees` WHERE sepa_run_id = ?');
            $stmt->execute([$id]);
            $stmt = $db->prepare('DELETE FROM `sepa_runs` WHERE id = ?');
            $stmt->execute([$id]);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Get summary metrics of the most recent SEPA debit run.
     *
     * @return array{date: string|null, total_amount: float, count: int}
     */
    public function getLastRunSummary(): array
    {
        $sql = "SELECT id, created_at, due_date FROM `sepa_runs` ORDER BY id DESC LIMIT 1";
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

        $aggSql = "SELECT SUM(amount) as total, COUNT(*) as cnt 
                   FROM `membership_fees` 
                   WHERE sepa_run_id = ?";
        $res = Database::fetchOne($aggSql, [$latest['id']]);

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
