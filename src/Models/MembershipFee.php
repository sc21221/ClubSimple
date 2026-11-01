<?php

declare(strict_types=1);

namespace Clubmanager\Models;

use Clubmanager\Core\Model;

/**
 * Membership Fee Run Record Model
 *
 * Tracks individual debits generated during SEPA runs.
 *
 * @package Clubmanager\Models
 * @author  Reinhard Schiessl
 *
 * @property int $id
 * @property int $member_id
 * @property int $fee_class_id
 * @property float|string $amount
 * @property string $due_date
 * @property string|null $paid_date
 * @property string|null $status
 * @property string $created_at
 */
class MembershipFee extends Model
{
    protected static string $table = 'membership_fees';
    protected static string $primaryKey = 'id';
}
