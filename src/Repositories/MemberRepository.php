<?php

declare(strict_types=1);

namespace Clubmanager\Repositories;

use Clubmanager\Models\Member;
use Clubmanager\Core\Database;
use Clubmanager\Services\AuditService;

/**
 * Member Repository
 *
 * Handles database operations for club members including filtering,
 * pagination, soft-deletion/archiving, and GDPR anonymization.
 *
 * @package Clubmanager\Repositories
 * @author  Reinhard Schiessl
 */
class MemberRepository
{
    /**
     * Search and filter members.
     *
     * @param array<string, mixed> $filters
     * @param int $limit
     * @param int $offset
     * @return array<int, Member>
     */
    public function search(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        [$whereSql, $params] = $this->buildFilterQuery($filters);

        $sql = "SELECT DISTINCT m.* FROM `members` m";
        if (!empty($filters['fee_class_id'])) {
            $sql .= " JOIN `member_fee_classes` mfc ON mfc.member_id = m.id AND mfc.is_active = 1";
        }

        if (!empty($whereSql)) {
            $sql .= " WHERE " . $whereSql;
        }

        $sql .= " ORDER BY m.last_name ASC, m.first_name ASC LIMIT {$limit} OFFSET {$offset}";

        $rows = Database::fetchAll($sql, $params);
        $members = [];
        foreach ($rows as $row) {
            $members[] = new Member($row, true);
        }
        return $members;
    }

    /**
     * Count total matching members for pagination.
     *
     * @param array<string, mixed> $filters
     * @return int
     */
    public function countSearch(array $filters = []): int
    {
        [$whereSql, $params] = $this->buildFilterQuery($filters);

        $sql = "SELECT COUNT(DISTINCT m.id) as cnt FROM `members` m";
        if (!empty($filters['fee_class_id'])) {
            $sql .= " JOIN `member_fee_classes` mfc ON mfc.member_id = m.id AND mfc.is_active = 1";
        }

        if (!empty($whereSql)) {
            $sql .= " WHERE " . $whereSql;
        }

        $res = Database::fetchOne($sql, $params);
        return (int) ($res['cnt'] ?? 0);
    }

    /**
     * Build WHERE clause and bound parameters from filters.
     *
     * @param array<string, mixed> $filters
     * @return array{0: string, 1: array<int, mixed>}
     */
    protected function buildFilterQuery(array $filters): array
    {
        $clauses = [];
        $params = [];

        if (!empty($filters['search'])) {
            $search = '%' . trim((string) $filters['search']) . '%';
            $clauses[] = "(m.first_name LIKE ? OR m.last_name LIKE ? OR m.email LIKE ? OR m.city LIKE ? OR m.street LIKE ?)";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        if (!empty($filters['status'])) {
            $clauses[] = "m.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['fee_class_id'])) {
            $clauses[] = "mfc.fee_class_id = ?";
            $params[] = (int) $filters['fee_class_id'];
        }

        return [implode(' AND ', $clauses), $params];
    }

    /**
     * Find member by ID.
     *
     * @param int $id
     * @return Member|null
     */
    public function findById(int $id): ?Member
    {
        return Member::find($id);
    }

    /**
     * Create a new member with audit logging.
     *
     * @param array<string, mixed> $data
     * @param int|null $userId
     * @return Member
     */
    public function create(array $data, ?int $userId = null): Member
    {
        $member = new Member($data, false);
        $member->save();

        AuditService::log(
            'MEMBER_CREATED',
            'members',
            (int) $member->id,
            null,
            $member->toArray(),
            $userId
        );

        return $member;
    }

    /**
     * Update an existing member with audit logging.
     *
     * @param Member $member
     * @param array<string, mixed> $data
     * @param int|null $userId
     * @return bool
     */
    public function update(Member $member, array $data, ?int $userId = null): bool
    {
        $old = $member->toArray();
        $member->fill($data);
        $saved = $member->save();

        if ($saved) {
            AuditService::log(
                'MEMBER_UPDATED',
                'members',
                (int) $member->id,
                $old,
                $member->toArray(),
                $userId
            );
        }

        return $saved;
    }

    /**
     * Archive a member (set status to Inaktiv) per GDPR guidelines.
     *
     * @param Member $member
     * @param int|null $userId
     * @return bool
     */
    public function archive(Member $member, ?int $userId = null): bool
    {
        $old = $member->toArray();
        $member->status = 'Inaktiv';
        if (empty($member->leave_date)) {
            $member->leave_date = date('Y-m-d');
        }
        $saved = $member->save();

        if ($saved) {
            AuditService::log(
                'MEMBER_ARCHIVED',
                'members',
                (int) $member->id,
                $old,
                $member->toArray(),
                $userId
            );
        }

        return $saved;
    }

    /**
     * Anonymize a member per GDPR Right to be Forgotten.
     *
     * @param Member $member
     * @param int|null $userId
     * @return bool
     */
    public function anonymize(Member $member, ?int $userId = null): bool
    {
        $old = $member->toArray();
        $anonymized = [
            'first_name' => 'DSGVO',
            'last_name' => 'Anonymisiert #' . $member->id,
            'email' => null,
            'phone' => null,
            'street' => 'Gesperrt',
            'zip' => '00000',
            'city' => 'Anonym',
            'bank_name' => null,
            'bank_bic' => null,
            'bank_iban' => null,
            'bank_holder' => null,
            'status' => 'Inaktiv',
        ];

        $member->fill($anonymized);
        $saved = $member->save();

        if ($saved) {
            AuditService::log(
                'MEMBER_ANONYMIZED',
                'members',
                (int) $member->id,
                $old,
                $member->toArray(),
                $userId
            );
        }

        return $saved;
    }

    /**
     * Permanently delete a member record.
     *
     * @param Member $member
     * @param int|null $userId
     * @return bool
     */
    public function delete(Member $member, ?int $userId = null): bool
    {
        $old = $member->toArray();
        $id = (int) $member->id;

        // Clean up fee assignments
        Database::execute("DELETE FROM `member_fee_classes` WHERE `member_id` = ?", [$id]);

        $deleted = $member->delete();
        if ($deleted) {
            AuditService::log(
                'MEMBER_DELETED',
                'members',
                $id,
                $old,
                null,
                $userId
            );
        }

        return $deleted;
    }

    /**
     * Get total active members count.
     *
     * @return int
     */
    public function getActiveCount(): int
    {
        return Member::count("status = 'Aktiv'");
    }

    /**
     * Get member counts grouped by status.
     *
     * @return array<string, int>
     */
    public function getCountsByStatus(): array
    {
        $sql = "SELECT status, COUNT(*) as cnt FROM `members` GROUP BY status";
        $rows = Database::fetchAll($sql);
        $counts = ['Aktiv' => 0, 'Inaktiv' => 0, 'Gesperrt' => 0];
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['cnt'];
        }
        return $counts;
    }
}
