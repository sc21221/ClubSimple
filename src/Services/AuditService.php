<?php

declare(strict_types=1);

namespace Clubmanager\Services;

use Clubmanager\Models\AuditLog;
use Clubmanager\Core\Logger;
use Clubmanager\Core\Session;

/**
 * Audit Trail Service
 *
 * Implements immutable change tracking on critical entities (members, clubs, fees)
 * writing both to the audit_logs database ledger and structured log files.
 *
 * @package Clubmanager\Services
 * @author  Reinhard Schiessl
 */
class AuditService
{
    /**
     * Record an audit event.
     *
     * @param string $action E.g., 'MEMBER_CREATED', 'MEMBER_UPDATED', 'MEMBER_ARCHIVED'
     * @param string $entityType E.g., 'members', 'clubs', 'fee_classes'
     * @param int|null $entityId
     * @param array<string, mixed>|null $oldValues
     * @param array<string, mixed>|null $newValues
     * @param int|null $userId
     * @return AuditLog|null
     */
    public static function log(
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null
    ): ?AuditLog {
        try {
            $effectiveUserId = $userId ?: (Session::get('user_id') ? (int) Session::get('user_id') : null);
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

            // Filter out sensitive fields like raw passwords from audit logs
            if ($oldValues) {
                unset($oldValues['password'], $oldValues['remember_token']);
            }
            if ($newValues) {
                unset($newValues['password'], $newValues['remember_token']);
            }

            $audit = new AuditLog([
                'timestamp' => date('Y-m-d H:i:s'),
                'user_id' => $effectiveUserId,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'old_values' => $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
                'new_values' => $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
                'ip_address' => $ipAddress,
            ]);
            $audit->save();

            // Emit to structured file logger
            Logger::info("Audit event recorded: {$action} on {$entityType} #{$entityId}", [
                'Action' => $action,
                'EntityType' => $entityType,
                'EntityId' => $entityId,
                'UserId' => $effectiveUserId,
                'IpAddress' => $ipAddress,
            ]);

            return $audit;
        } catch (\Throwable $e) {
            Logger::error("Failed to record audit trail log: " . $e->getMessage());
            return null;
        }
    }
}
