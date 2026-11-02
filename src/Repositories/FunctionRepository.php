<?php

declare(strict_types=1);

namespace Clubmanager\Repositories;

use Clubmanager\Core\Database;
use Clubmanager\Models\FunctionModel;
use PDOException;

class FunctionRepository
{
    /** @return array<int, FunctionModel> */
    public function getAll(string $sort = 'name', string $direction = 'asc'): array
    {
        $columns = ['name' => 'name'];
        $column = $columns[$sort] ?? $columns['name'];
        $direction = $direction === 'desc' ? 'DESC' : 'ASC';
        return FunctionModel::all("{$column} {$direction}, id ASC");
    }

    public function findById(int $id): ?FunctionModel
    {
        return FunctionModel::find($id);
    }

    public function create(array $data): FunctionModel
    {
        return FunctionModel::create(['name' => $data['name']]);
    }

    public function update(FunctionModel $function, array $data): bool
    {
        $function->name = $data['name'];
        return $function->save();
    }

    public function delete(FunctionModel $function): bool
    {
        try {
            return Database::execute(
                'DELETE FROM `club_functions` WHERE `id` = ? AND NOT EXISTS (SELECT 1 FROM `member_functions` WHERE `function_id` = ?)',
                [(int) $function->id, (int) $function->id]
            ) === 1;
        } catch (PDOException $e) {
            if (in_array((string) $e->getCode(), ['23000', '23503'], true)) { return false; }
            throw $e;
        }
    }

    /** @return array<int, object> */
    public function getForMember(int $memberId): array
    {
        $rows = Database::fetchAll(
            'SELECT mf.id, mf.function_id, f.name, mf.start_date, mf.end_date, mf.description FROM `member_functions` mf JOIN `club_functions` f ON f.id = mf.function_id WHERE mf.member_id = ? ORDER BY f.name ASC, mf.start_date ASC, mf.id ASC',
            [$memberId]
        );
        return array_map(static fn(array $row): object => (object) $row, $rows);
    }

    /** @return array<int, object> */
    public function getMembers(int $functionId, bool $history = false): array
    {
        $today = date('Y-m-d');
        $sql = 'SELECT m.*, mf.id AS assignment_id, mf.start_date, mf.end_date, mf.description FROM `member_functions` mf JOIN `members` m ON m.id = mf.member_id WHERE mf.function_id = ? AND mf.start_date <= ?';
        $params = [$functionId, $today];
        if (!$history) {
            $sql .= ' AND (mf.end_date IS NULL OR mf.end_date >= ?)';
            $params[] = $today;
        }
        $sql .= ' ORDER BY m.last_name ASC, m.first_name ASC, m.id ASC, mf.start_date ASC, mf.id ASC';
        return array_map(static fn(array $row): object => (object) $row, Database::fetchAll($sql, $params));
    }

    public function assignToMember(int $memberId, int $functionId, string $startDate, ?string $endDate, ?string $description): bool
    {
        try {
            return Database::execute(
                'INSERT INTO `member_functions` (`member_id`, `function_id`, `start_date`, `end_date`, `description`) SELECT ?, ?, ?, ?, ? WHERE EXISTS (SELECT 1 FROM `members` WHERE `id` = ?) AND EXISTS (SELECT 1 FROM `club_functions` WHERE `id` = ?)',
                [$memberId, $functionId, $startDate, $endDate, $description, $memberId, $functionId]
            ) === 1;
        } catch (PDOException $e) {
            if (in_array((string) $e->getCode(), ['23000', '23503', '23505'], true)) { return false; }
            throw $e;
        }
    }

    public function removeAssignment(int $memberId, int $assignmentId): bool
    {
        return Database::execute('DELETE FROM `member_functions` WHERE `id` = ? AND `member_id` = ?', [$assignmentId, $memberId]) === 1;
    }
}