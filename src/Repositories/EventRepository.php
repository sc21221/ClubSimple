<?php

declare(strict_types=1);

namespace Clubmanager\Repositories;

use Clubmanager\Core\Database;
use Clubmanager\Models\Event;
use PDOException;

class EventRepository
{
    /** @return array<int, Event> */
    public function getAll(string $sort = 'name', string $direction = 'asc'): array
    {
        $columns = ['name' => 'name'];
        $column = $columns[$sort] ?? $columns['name'];
        $direction = $direction === 'desc' ? 'DESC' : 'ASC';
        return Event::all("{$column} {$direction}, id ASC");
    }

    public function findById(int $id): ?Event
    {
        return Event::find($id);
    }

    public function create(array $data): Event
    {
        return Event::create(['name' => $data['name']]);
    }

    public function update(Event $event, array $data): bool
    {
        $event->name = $data['name'];
        return $event->save();
    }

    public function delete(Event $event): bool
    {
        try {
            return Database::execute(
                'DELETE FROM `events` WHERE `id` = ? AND NOT EXISTS (SELECT 1 FROM `member_events` WHERE `event_id` = ?)',
                [(int) $event->id, (int) $event->id]
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
            'SELECT me.id, me.event_id, e.name, me.event_date, me.description FROM `member_events` me JOIN `events` e ON e.id = me.event_id WHERE me.member_id = ? ORDER BY e.name ASC, me.event_date ASC, me.id ASC',
            [$memberId]
        );
        return array_map(static fn(array $row): object => (object) $row, $rows);
    }

    /** @return array<int, object> */
    public function getMembers(int $eventId): array
    {
        $rows = Database::fetchAll(
            'SELECT m.*, me.id AS assignment_id, me.event_date, me.description FROM `member_events` me JOIN `members` m ON m.id = me.member_id WHERE me.event_id = ? ORDER BY m.last_name ASC, m.first_name ASC, m.id ASC, me.event_date ASC, me.id ASC',
            [$eventId]
        );
        return array_map(static fn(array $row): object => (object) $row, $rows);
    }

    public function assignToMember(int $memberId, int $eventId, string $eventDate, ?string $description): bool
    {
        try {
            return Database::execute(
                'INSERT INTO `member_events` (`member_id`, `event_id`, `event_date`, `description`) SELECT ?, ?, ?, ? WHERE EXISTS (SELECT 1 FROM `members` WHERE `id` = ?) AND EXISTS (SELECT 1 FROM `events` WHERE `id` = ?)',
                [$memberId, $eventId, $eventDate, $description, $memberId, $eventId]
            ) === 1;
        } catch (PDOException $e) {
            if (in_array((string) $e->getCode(), ['23000', '23503'], true)) { return false; }
            throw $e;
        }
    }

    public function removeAssignment(int $memberId, int $assignmentId): bool
    {
        return Database::execute('DELETE FROM `member_events` WHERE `id` = ? AND `member_id` = ?', [$assignmentId, $memberId]) === 1;
    }
}