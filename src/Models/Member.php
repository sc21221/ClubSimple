<?php

declare(strict_types=1);

namespace Clubmanager\Models;

use Clubmanager\Core\Model;
use Clubmanager\Core\Database;
use DateTimeImmutable;

/**
 * Member Model
 *
 * Represents club member records including contact, address, status,
 * demographic data, and SEPA banking mandates.
 *
 * @package Clubmanager\Models
 * @author  Reinhard Schiessl
 *
 * @property int $id
 * @property int $club_id
 * @property string|null $salutation
 * @property string|null $sex
 * @property string $first_name
 * @property string $last_name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $membership_type
 * @property string|null $status
 * @property int|null $invoice_marker
 * @property string|null $street
 * @property string|null $zip
 * @property string|null $city
 * @property string|null $country
 * @property string $join_date
 * @property string|null $leave_date
 * @property string $birth_date
 * @property string|null $bank_name
 * @property string|null $bank_bic
 * @property string|null $bank_iban
 * @property string|null $bank_holder
 * @property string $created_at
 * @property string|null $updated_by
 * @property string $updated_at
 */
class Member extends Model
{
    protected static string $table = 'members';
    protected static string $primaryKey = 'id';
    protected static bool $tracksUpdatedBy = true;

    /**
     * Get member's full name.
     *
     * @return string
     */
    public function getFullName(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }

    /**
     * Calculate current age or age at a specific reference date.
     *
     * @param string|null $atDate Y-m-d format
     * @return int
     */
    public function getAge(?string $atDate = null): int
    {
        if (empty($this->birth_date) || $this->birth_date === '0000-00-00') {
            return 0;
        }

        try {
            $birth = new DateTimeImmutable($this->birth_date);
            $ref = $atDate ? new DateTimeImmutable($atDate) : new DateTimeImmutable();
            return $birth->diff($ref)->y;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Calculate membership duration in full years.
     *
     * @param string|null $atDate
     * @return int
     */
    public function getYearsOfMembership(?string $atDate = null): int
    {
        if (empty($this->join_date) || $this->join_date === '0000-00-00') {
            return 0;
        }

        try {
            $join = new DateTimeImmutable($this->join_date);
            $ref = $atDate ? new DateTimeImmutable($atDate) : new DateTimeImmutable();
            return $join->diff($ref)->y;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Check if direct debit can be executed for this member.
     *
     * @return bool
     */
    public function isDirectDebitEligible(): bool
    {
        return $this->status === 'Aktiv'
            && empty($this->invoice_marker)
            && !empty($this->bank_iban);
    }

    /**
     * Fetch active fee classes assigned to this member.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getActiveFeeClasses(): array
    {
        $sql = "SELECT mfc.*, fc.name as fee_class_name, fc.base_amount, fc.currency, fc.billing_cycle, fc.min_age, fc.max_age
                FROM `member_fee_classes` mfc
                JOIN `fee_classes` fc ON fc.id = mfc.fee_class_id
                WHERE mfc.member_id = ? AND mfc.is_active = 1
                ORDER BY fc.name";
        return Database::fetchAll($sql, [$this->id]);
    }

    /**
     * Calculate total recurring fee amount for this member.
     *
     * @return float
     */
    public function getTotalActiveFeeAmount(): float
    {
        $fees = $this->getActiveFeeClasses();
        $total = 0.0;
        foreach ($fees as $fee) {
            $amount = ($fee['custom_amount'] !== null && $fee['custom_amount'] !== '')
                ? (float) $fee['custom_amount']
                : (float) $fee['base_amount'];
            $total += $amount;
        }
        return $total;
    }
}
