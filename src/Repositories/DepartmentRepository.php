<?php

declare(strict_types=1);

namespace Clubmanager\Repositories;

use Clubmanager\Core\Database;
use Clubmanager\Models\Department;
use PDOException;

class DepartmentRepository
{
    /** @return array<int, Department> */
    public function getAll(string $sort = 'name', string $direction = 'asc'): array
    {
        $columns = ['name' => 'name'];
        $column = $columns[$sort] ?? $columns['name'];
        $direction = $direction === 'desc' ? 'DESC' : 'ASC';
        return Department::all("{$column} {$direction}, id ASC");
    }

    public function findById(int $id): ?Department
    {
        return Department::find($id);
    }

    public function create(array $data): Department
    {
        return Department::create(['name' => $data['name']]);
    }

    public function update(Department $department, array $data): bool
    {
        $department->name = $data['name'];
        return $department->save();
    }

    public function delete(Department $department): bool
    {
        try {
            return Database::execute(
                'DELETE FROM `departments` WHERE `id` = ? AND NOT EXISTS (SELECT 1 FROM `member_departments` WHERE `department_id` = ?)',
                [(int) $department->id, (int) $department->id]
            ) === 1;
        } catch (PDOException $e) {
            if (in_array((string) $e->getCode(), ['23000', '23503'], true)) {
                return false;
            }
            throw $e;
        }
    }

    /** @return array<int, object> */
    public function getForMember(int $memberId): array
    {
        $rows = Database::fetchAll(
            'SELECT md.id, md.department_id, d.name, md.assigned_date, md.description FROM `member_departments` md JOIN `departments` d ON d.id = md.department_id WHERE md.member_id = ? ORDER BY d.name ASC, md.id ASC',
            [$memberId]
        );
        return array_map(static fn(array $row): object => (object) $row, $rows);
    }

    /** @return array<int, object> */
    public function getMembers(int $departmentId): array
    {
        $rows = Database::fetchAll(
            'SELECT m.*, md.id AS assignment_id, md.assigned_date, md.description FROM `member_departments` md JOIN `members` m ON m.id = md.member_id WHERE md.department_id = ? ORDER BY m.last_name ASC, m.first_name ASC, m.id ASC',
            [$departmentId]
        );
        return array_map(static fn(array $row): object => (object) $row, $rows);
    }

    public function assignToMember(int $memberId, int $departmentId, ?string $assignedDate, ?string $description): bool
    {
        try {
            return Database::execute(
                'INSERT INTO `member_departments` (`member_id`, `department_id`, `assigned_date`, `description`) SELECT ?, ?, ?, ? WHERE EXISTS (SELECT 1 FROM `members` WHERE `id` = ?) AND EXISTS (SELECT 1 FROM `departments` WHERE `id` = ?)',
                [$memberId, $departmentId, $assignedDate, $description, $memberId, $departmentId]
            ) === 1;
        } catch (PDOException $e) {
            if (in_array((string) $e->getCode(), ['23000', '23503', '23505'], true)) {
                return false;
            }
            throw $e;
        }
    }

    public function removeAssignment(int $memberId, int $assignmentId): bool
    {
        return Database::execute('DELETE FROM `member_departments` WHERE `id` = ? AND `member_id` = ?', [$assignmentId, $memberId]) === 1;
    }
}