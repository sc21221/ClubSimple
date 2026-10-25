<?php

declare(strict_types=1);

namespace Clubmanager\Models;

use Clubmanager\Core\Model;

/**
 * Member Fee Class Assignment Model
 *
 * Represents the mapping between members and their assigned fee classes,
 * supporting validity dates and custom amount overrides.
 *
 * @package Clubmanager\Models
 * @author  Reinhard Schiessl
 *
 * @property int $id
 * @property int $member_id
 * @property int $fee_class_id
 * @property float|string|null $custom_amount
 * @property string $start_date
 * @property string|null $end_date
 * @property int $is_active
 * @property string $created_at
 */
class MemberFeeClass extends Model
{
    protected static string $table = 'member_fee_classes';
    protected static string $primaryKey = 'id';
}
