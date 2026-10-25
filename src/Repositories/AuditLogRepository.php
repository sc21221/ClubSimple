<?php

declare(strict_types=1);

namespace Clubmanager\Repositories;

use Clubmanager\Models\AuditLog;
use Clubmanager\Core\Database;

/**
 * Audit Log Repository
 *
 * @package Clubmanager\Repositories
 * @author  Reinhard Schiessl
 */
class AuditLogRepository
{
    /**
     * Search and filter audit logs.
     *
     * @param array<string, mixed> $filters
     * @param int $limit
     * @param int $offset
     * @return array<int, AuditLog>
     */
    public function search(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        [$whereSql, $params] = $this->buildFilterQuery($filters);

        $sql = "SELECT * FROM `audit_logs`";
        if (!empty($whereSql)) {
            $sql .= " WHERE " . $whereSql;
        }
        $sql .= " ORDER BY id DESC LIMIT {$limit} OFFSET {$offset}";

        $rows = Database::fetchAll($sql, $params);
        $logs = [];
        foreach ($rows as $row) {
            $logs[] = new AuditLog($row, true);
        }
        return $logs;
    }

    /**
     * Count total matching audit logs.
     *
     * @param array<string, mixed> $filters
     * @return int
     */
    public function countSearch(array $filters = []): int
    {
        [$whereSql, $params] = $this->buildFilterQuery($filters);

        $sql = "SELECT COUNT(*) as cnt FROM `audit_logs`";
        if (!empty($whereSql)) {
            $sql .= " WHERE " . $whereSql;
        }

        $res = Database::fetchOne($sql, $params);
        return (int) ($res['cnt'] ?? 0);
    }

    /**
     * Build WHERE clause for audit queries.
     *
     * @param array<string, mixed> $filters
     * @return array{0: string, 1: array<int, mixed>}
     */
    protected function buildFilterQuery(array $filters): array
    {
        $clauses = [];
        $params = [];

        if (!empty($filters['entity_type'])) {
            $clauses[] = "entity_type = ?";
            $params[] = $filters['entity_type'];
        }

        if (!empty($filters['entity_id'])) {
            $clauses[] = "entity_id = ?";
            $params[] = (int) $filters['entity_id'];
        }

        if (!empty($filters['user_id'])) {
            $clauses[] = "user_id = ?";
            $params[] = (int) $filters['user_id'];
        }

        if (!empty($filters['action'])) {
            $clauses[] = "action = ?";
            $params[] = $filters['action'];
        }

        if (!empty($filters['date_from'])) {
            $clauses[] = "timestamp >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $clauses[] = "timestamp <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        return [implode(' AND ', $clauses), $params];
    }
}
