<?php

declare(strict_types=1);

namespace Clubmanager\Models;

use Clubmanager\Core\Model;
use Clubmanager\Core\Database;

/**
 * Fee Class Model
 *
 * Represents membership fee categories and their age boundaries.
 *
 * @package Clubmanager\Models
 * @author  Reinhard Schiessl
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property float|string $base_amount
 * @property string $currency
 * @property string $billing_cycle
 * @property int $is_active
 * @property int $min_age
 * @property int $max_age
 * @property string $created_at
 * @property string $updated_at
 */
class FeeClass extends Model
{
    protected static string $table = 'fee_classes';
    protected static string $primaryKey = 'id';

    /**
     * Determine if a given age matches this fee class's boundaries.
     *
     * @param int $age
     * @return bool
     */
    public function matchesAge(int $age): bool
    {
        $min = (int) ($this->min_age ?? 0);
        $max = (int) ($this->max_age ?? 999);
        return $age >= $min && $age <= $max;
    }

    /**
     * Count currently active members assigned to this fee class.
     *
     * @return int
     */
    public function getActiveMembersCount(): int
    {
        $sql = "SELECT COUNT(DISTINCT mfc.member_id) as cnt
                FROM `member_fee_classes` mfc
                JOIN `members` m ON m.id = mfc.member_id
                WHERE mfc.fee_class_id = ? AND mfc.is_active = 1 AND m.status = 'Aktiv'";
        $res = Database::fetchOne($sql, [$this->id]);
        return (int) ($res['cnt'] ?? 0);
    }
}
