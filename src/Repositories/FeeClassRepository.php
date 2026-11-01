<?php

declare(strict_types=1);

namespace Clubmanager\Repositories;

use Clubmanager\Models\FeeClass;
use Clubmanager\Models\MemberFeeClass;
use Clubmanager\Core\Database;
use Clubmanager\Services\AuditService;

/**
 * Fee Class Repository
 *
 * @package Clubmanager\Repositories
 * @author  Reinhard Schiessl
 */
class FeeClassRepository
{
    /**
     * Get all fee classes ordered by name.
     *
     * @return array<int, FeeClass>
     */
    public function getAll(): array
    {
        return FeeClass::all('name ASC');
    }

    /**
     * Get all active fee classes.
     *
     * @return array<int, FeeClass>
     */
    public function getActive(): array
    {
        return FeeClass::where('is_active = 1', [], 'name ASC');
    }

    /**
     * Find fee class by ID.
     *
     * @param int $id
     * @return FeeClass|null
     */
    public function findById(int $id): ?FeeClass
    {
        return FeeClass::find($id);
    }

    /**
     * Find matching fee class for a given age.
     * Matches active fee classes where min_age <= $age and max_age >= $age.
     *
     * @param int $age
     * @return FeeClass|null
     */
    public function findForAge(int $age): ?FeeClass
    {
        $classes = FeeClass::where('is_active = 1 AND min_age <= ? AND max_age >= ?', [$age, $age], 'min_age DESC');
        return $classes[0] ?? null;
    }

    /**
     * Create a new fee class.
     *
     * @param array<string, mixed> $data
     * @param int|null $userId
     * @return FeeClass
     */
    public function create(array $data, ?int $userId = null): FeeClass
    {
        $feeClass = new FeeClass($data, false);
        $feeClass->save();

        AuditService::log('FEE_CLASS_CREATED', 'fee_classes', (int) $feeClass->id, null, $feeClass->toArray(), $userId);
        return $feeClass;
    }

    /**
     * Update an existing fee class.
     *
     * @param FeeClass $feeClass
     * @param array<string, mixed> $data
     * @param int|null $userId
     * @return bool
     */
    public function update(FeeClass $feeClass, array $data, ?int $userId = null): bool
    {
        $old = $feeClass->toArray();
        $feeClass->fill($data);
        $saved = $feeClass->save();

        if ($saved) {
            AuditService::log('FEE_CLASS_UPDATED', 'fee_classes', (int) $feeClass->id, $old, $feeClass->toArray(), $userId);
        }

        return $saved;
    }

    /**
     * Delete a fee class.
     *
     * @param FeeClass $feeClass
     * @param int|null $userId
     * @return bool
     */
    public function delete(FeeClass $feeClass, ?int $userId = null): bool
    {
        $old = $feeClass->toArray();
        $id = (int) $feeClass->id;

        $references = Database::fetchOne(
            'SELECT EXISTS(SELECT 1 FROM `membership_fees` WHERE `fee_class_id` = ?) AS has_fees,
                    EXISTS(SELECT 1 FROM `member_fee_classes` WHERE `fee_class_id` = ?) AS has_assignments',
            [$id, $id]
        );
        if (!empty($references['has_fees']) || !empty($references['has_assignments'])) {
            return false;
        }

        $deleted = $feeClass->delete();

        if ($deleted) {
            AuditService::log('FEE_CLASS_DELETED', 'fee_classes', $id, $old, null, $userId);
        }

        return $deleted;
    }

    /**
     * Assign a fee class to a member.
     *
     * @param int $memberId
     * @param int $feeClassId
     * @param float|null $customAmount
     * @param string|null $startDate
     * @param string|null $endDate
     * @param int|null $userId
     * @return MemberFeeClass
     */
    public function assignToMember(
        int $memberId,
        int $feeClassId,
        ?float $customAmount = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?int $userId = null
    ): MemberFeeClass {
        $startDate = $startDate ?: date('Y-m-d');

        // Check if assignment already exists
        $existing = MemberFeeClass::where(
            'member_id = ? AND fee_class_id = ? AND start_date = ?',
            [$memberId, $feeClassId, $startDate]
        );

        if (!empty($existing)) {
            $assignment = $existing[0];
            $assignment->custom_amount = $customAmount;
            $assignment->end_date = $endDate;
            $assignment->is_active = 1;
            $assignment->save();
        } else {
            $assignment = new MemberFeeClass([
                'member_id' => $memberId,
                'fee_class_id' => $feeClassId,
                'custom_amount' => $customAmount,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'is_active' => 1,
            ]);
            $assignment->save();
        }

        AuditService::log('MEMBER_FEE_ASSIGNED', 'member_fee_classes', (int) $assignment->id, null, $assignment->toArray(), $userId);
        return $assignment;
    }

    /**
     * Change the fee class of a single assignment, preserving its other fields.
     */
    public function updateAssignment(int $memberId, int $assignmentId, int $feeClassId, ?int $userId = null): bool
    {
        $assignment = MemberFeeClass::find($assignmentId);
        if (!$assignment || (int) $assignment->member_id !== $memberId) {
            return false;
        }

        $conflicts = MemberFeeClass::where(
            'member_id = ? AND fee_class_id = ? AND start_date = ? AND id <> ?',
            [$memberId, $feeClassId, $assignment->start_date, $assignmentId]
        );
        if ($conflicts) {
            return false;
        }

        if ((int) $assignment->fee_class_id === $feeClassId) {
            return true;
        }

        $old = $assignment->toArray();
        $assignment->fee_class_id = $feeClassId;
        try {
            $saved = $assignment->save();
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000' || $e->getCode() === '23505') {
                return false;
            }
            throw $e;
        }

        if ($saved) {
            AuditService::log('MEMBER_FEE_UPDATED', 'member_fee_classes', $assignmentId, $old, $assignment->toArray(), $userId);
        }
        return $saved;
    }

    /**
     * Remove or deactivate a member fee class assignment.
     *
     * @param int $assignmentId
     * @param int|null $userId
     * @return bool
     */
    public function removeAssignment(int $assignmentId, ?int $userId = null): bool
    {
        $assignment = MemberFeeClass::find($assignmentId);
        if (!$assignment) {
            return false;
        }

        $old = $assignment->toArray();
        $deleted = $assignment->delete();

        if ($deleted) {
            AuditService::log('MEMBER_FEE_REMOVED', 'member_fee_classes', $assignmentId, $old, null, $userId);
        }

        return $deleted;
    }

    /**
     * Total count of fee classes.
     *
     * @return int
     */
    public function getTotalCount(): int
    {
        return FeeClass::count();
    }
}
