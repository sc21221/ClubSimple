<?php

declare(strict_types=1);

namespace Clubmanager\Models;

use Clubmanager\Core\Model;
use Clubmanager\Core\Database;

/**
 * Audit Log / Journal Model
 *
 * Immutable ledger tracking all data modifications, creations, and deletions.
 *
 * @package Clubmanager\Models
 * @author  Reinhard Schiessl
 *
 * @property int|string $id
 * @property string $timestamp
 * @property int|string|null $user_id
 * @property string $action
 * @property string $entity_type
 * @property int|string|null $entity_id
 * @property string|null $old_values
 * @property string|null $new_values
 * @property string|null $ip_address
 */
class AuditLog extends Model
{
    protected static string $table = 'audit_logs';
    protected static string $primaryKey = 'id';

    /**
     * Decode old values from JSON.
     *
     * @return array<string, mixed>|null
     */
    public function getOldValues(): ?array
    {
        return !empty($this->old_values) ? json_decode((string) $this->old_values, true) : null;
    }

    /**
     * Decode new values from JSON.
     *
     * @return array<string, mixed>|null
     */
    public function getNewValues(): ?array
    {
        return !empty($this->new_values) ? json_decode((string) $this->new_values, true) : null;
    }

    /**
     * Get author user name if associated.
     *
     * @return string
     */
    public function getUserName(): string
    {
        if (empty($this->user_id)) {
            return 'System';
        }
        $user = User::find($this->user_id);
        return $user ? $user->name : "User #{$this->user_id}";
    }
}
