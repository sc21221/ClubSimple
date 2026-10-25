<?php

declare(strict_types=1);

namespace Clubmanager\Models;

use Clubmanager\Core\Model;

/**
 * User Account Model
 *
 * Handles staff and administrator authentication, locale preferences, and authorization.
 *
 * @package Clubmanager\Models
 * @author  Reinhard Schiessl
 *
 * @property int|string $id
 * @property string $name
 * @property string $email
 * @property string|null $email_verified_at
 * @property string $password
 * @property string|null $phone
 * @property int $admin
 * @property string|null $profile_image
 * @property string $locale
 * @property int|string|null $club_id
 * @property int|string|null $created_by
 * @property string|null $remember_token
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class User extends Model
{
    protected static string $table = 'users';
    protected static string $primaryKey = 'id';

    /**
     * Check if user has administrator privileges.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return (bool) ($this->admin ?? false);
    }

    /**
     * Set hashed password.
     *
     * @param string $rawPassword
     * @return void
     */
    public function setPassword(string $rawPassword): void
    {
        $this->password = password_hash($rawPassword, PASSWORD_DEFAULT);
    }

    /**
     * Verify raw password against stored hash.
     *
     * @param string $rawPassword
     * @return bool
     */
    public function verifyPassword(string $rawPassword): bool
    {
        return password_verify($rawPassword, (string) $this->password);
    }
}
