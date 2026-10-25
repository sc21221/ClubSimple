<?php

declare(strict_types=1);

namespace Clubmanager\Repositories;

use Clubmanager\Models\User;
use Clubmanager\Services\AuditService;

/**
 * User Repository
 *
 * @package Clubmanager\Repositories
 * @author  Reinhard Schiessl
 */
class UserRepository
{
    /**
     * Find user by email address.
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User
    {
        $users = User::where('email = ?', [trim($email)], 'id ASC', 1);
        return $users[0] ?? null;
    }

    /**
     * Find user by ID.
     *
     * @param int|string $id
     * @return User|null
     */
    public function findById(int|string $id): ?User
    {
        return User::find($id);
    }

    /**
     * Get all users.
     *
     * @return array<int, User>
     */
    public function getAll(): array
    {
        return User::all('name ASC');
    }

    /**
     * Create a new user.
     *
     * @param array<string, mixed> $data
     * @param int|null $authorId
     * @return User
     */
    public function create(array $data, ?int $authorId = null): User
    {
        $user = new User([
            'name' => trim((string)$data['name']),
            'email' => strtolower(trim((string)$data['email'])),
            'phone' => $data['phone'] ?? null,
            'admin' => !empty($data['admin']) ? 1 : 0,
            'locale' => $data['locale'] ?? 'de',
            'club_id' => $data['club_id'] ?? 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if (!empty($data['password'])) {
            $user->setPassword((string)$data['password']);
        }

        $user->save();

        AuditService::log('USER_CREATED', 'users', (int) $user->id, null, $user->toArray(), $authorId);
        return $user;
    }

    /**
     * Update an existing user.
     *
     * @param User $user
     * @param array<string, mixed> $data
     * @param int|null $authorId
     * @return bool
     */
    public function update(User $user, array $data, ?int $authorId = null): bool
    {
        $old = $user->toArray();

        if (isset($data['name'])) {
            $user->name = trim((string)$data['name']);
        }
        if (isset($data['email'])) {
            $user->email = strtolower(trim((string)$data['email']));
        }
        if (isset($data['phone'])) {
            $user->phone = $data['phone'];
        }
        if (isset($data['admin'])) {
            $user->admin = !empty($data['admin']) ? 1 : 0;
        }
        if (isset($data['locale'])) {
            $user->locale = $data['locale'];
        }
        if (!empty($data['password'])) {
            $user->setPassword((string)$data['password']);
        }

        $user->updated_at = date('Y-m-d H:i:s');
        $saved = $user->save();

        if ($saved) {
            AuditService::log('USER_UPDATED', 'users', (int) $user->id, $old, $user->toArray(), $authorId);
        }

        return $saved;
    }

    /**
     * Delete a user.
     *
     * @param User $user
     * @param int|null $authorId
     * @return bool
     */
    public function delete(User $user, ?int $authorId = null): bool
    {
        $old = $user->toArray();
        $id = (int) $user->id;
        $deleted = $user->delete();

        if ($deleted) {
            AuditService::log('USER_DELETED', 'users', $id, $old, null, $authorId);
        }

        return $deleted;
    }
}
