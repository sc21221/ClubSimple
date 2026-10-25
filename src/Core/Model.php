<?php

declare(strict_types=1);

namespace Clubmanager\Core;

use PDO;

/**
 * Base Model / Data Mapper
 *
 * Implements lightweight active record pattern with dirty tracking,
 * automatic timestamp management, and change audit hooks.
 *
 * @package Clubmanager\Core
 * @author  Reinhard Schiessl
 */
abstract class Model
{
    /**
     * @var string Database table name
     */
    protected static string $table = '';

    /**
     * @var string Primary key column name
     */
    protected static string $primaryKey = 'id';

    /**
     * @var array<string, mixed> Current model attributes
     */
    protected array $attributes = [];

    /**
     * @var array<string, mixed> Original attributes loaded from database
     */
    protected array $original = [];

    /**
     * @var bool Indicates if the model exists in the database
     */
    protected bool $exists = false;

    /**
     * Constructor.
     *
     * @param array<string, mixed> $attributes
     * @param bool $exists
     */
    public function __construct(array $attributes = [], bool $exists = false)
    {
        $this->fill($attributes);
        $this->exists = $exists;
        if ($exists) {
            $this->original = $this->attributes;
        }
    }

    /**
     * Get table name.
     *
     * @return string
     */
    public static function getTable(): string
    {
        return static::$table;
    }

    /**
     * Get primary key column name.
     *
     * @return string
     */
    public static function getPrimaryKey(): string
    {
        return static::$primaryKey;
    }

    /**
     * Magic getter for attributes.
     *
     * @param string $key
     * @return mixed
     */
    public function __get(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Magic setter for attributes.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function __set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Magic isset check.
     *
     * @param string $key
     * @return bool
     */
    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Populate model attributes.
     *
     * @param array<string, mixed> $attributes
     * @return static
     */
    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = $value;
        }
        return $this;
    }

    /**
     * Convert attributes to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * Determine if model exists in database.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return $this->exists;
    }

    /**
     * Find a record by its primary key.
     *
     * @param mixed $id
     * @return static|null
     */
    public static function find(mixed $id): ?static
    {
        if ($id === null || $id === '') {
            return null;
        }

        $table = static::$table;
        $pk = static::$primaryKey;
        $row = Database::fetchOne("SELECT * FROM `{$table}` WHERE `{$pk}` = ? LIMIT 1", [$id]);

        if (!$row) {
            return null;
        }

        return new static($row, true);
    }

    /**
     * Fetch all records matching conditions.
     *
     * @param string $whereClause
     * @param array<int|string, mixed> $params
     * @param string $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return array<int, static>
     */
    public static function where(string $whereClause, array $params = [], string $orderBy = '', ?int $limit = null, ?int $offset = null): array
    {
        $table = static::$table;
        $sql = "SELECT * FROM `{$table}` WHERE {$whereClause}";
        if (!empty($orderBy)) {
            $sql .= " ORDER BY {$orderBy}";
        }
        if ($limit !== null) {
            $sql .= " LIMIT {$limit}";
            if ($offset !== null) {
                $sql .= " OFFSET {$offset}";
            }
        }

        $rows = Database::fetchAll($sql, $params);
        $models = [];
        foreach ($rows as $row) {
            $models[] = new static($row, true);
        }
        return $models;
    }

    /**
     * Fetch all records from the table.
     *
     * @param string $orderBy
     * @return array<int, static>
     */
    public static function all(string $orderBy = ''): array
    {
        $table = static::$table;
        $pk = static::$primaryKey;
        $order = !empty($orderBy) ? $orderBy : "`{$pk}` ASC";
        $rows = Database::fetchAll("SELECT * FROM `{$table}` ORDER BY {$order}");

        $models = [];
        foreach ($rows as $row) {
            $models[] = new static($row, true);
        }
        return $models;
    }

    /**
     * Count total rows matching criteria.
     *
     * @param string $whereClause
     * @param array<int|string, mixed> $params
     * @return int
     */
    public static function count(string $whereClause = '', array $params = []): int
    {
        $table = static::$table;
        $sql = "SELECT COUNT(*) as cnt FROM `{$table}`";
        if (!empty($whereClause)) {
            $sql .= " WHERE {$whereClause}";
        }
        $row = Database::fetchOne($sql, $params);
        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Create and insert a new model instance.
     *
     * @param array<string, mixed> $attributes
     * @return static
     */
    public static function create(array $attributes): static
    {
        $model = new static($attributes, false);
        $model->save();
        return $model;
    }

    /**
     * Save the model to database (INSERT or UPDATE).
     *
     * @return bool
     */
    public function save(): bool
    {
        $table = static::$table;
        $pk = static::$primaryKey;

        // Auto manage updated_by if logged in
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user_name'])) {
            $this->attributes['updated_by'] = $_SESSION['user_name'];
        }

        if ($this->exists) {
            // UPDATE
            $dirty = $this->getDirty();
            if (empty($dirty)) {
                return true; // No changes
            }

            $setParts = [];
            $params = [];
            foreach ($this->attributes as $key => $val) {
                if ($key === $pk) {
                    continue;
                }
                $setParts[] = "`{$key}` = ?";
                $params[] = $val;
            }

            $params[] = $this->attributes[$pk];
            $sql = "UPDATE `{$table}` SET " . implode(', ', $setParts) . " WHERE `{$pk}` = ?";
            Database::execute($sql, $params);

            $this->original = $this->attributes;
            return true;
        } else {
            // INSERT
            $columns = [];
            $placeholders = [];
            $params = [];

            foreach ($this->attributes as $key => $val) {
                $columns[] = "`{$key}`";
                $placeholders[] = "?";
                $params[] = $val;
            }

            $sql = "INSERT INTO `{$table}` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
            Database::execute($sql, $params);

            $id = Database::lastInsertId();
            if ($id && !isset($this->attributes[$pk])) {
                $this->attributes[$pk] = (int) $id;
            }

            $this->exists = true;
            $this->original = $this->attributes;
            return true;
        }
    }

    /**
     * Delete this record from database.
     *
     * @return bool
     */
    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        $table = static::$table;
        $pk = static::$primaryKey;
        $id = $this->attributes[$pk] ?? null;

        if ($id === null) {
            return false;
        }

        Database::execute("DELETE FROM `{$table}` WHERE `{$pk}` = ?", [$id]);
        $this->exists = false;
        return true;
    }

    /**
     * Get changed attributes compared to original values.
     *
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public function getDirty(): array
    {
        $dirty = [];
        foreach ($this->attributes as $key => $value) {
            $originalVal = $this->original[$key] ?? null;
            if ($originalVal != $value) {
                $dirty[$key] = [
                    'old' => $originalVal,
                    'new' => $value,
                ];
            }
        }
        return $dirty;
    }

    /**
     * Get original attribute value before changes.
     *
     * @param string $key
     * @return mixed
     */
    public function getOriginal(string $key): mixed
    {
        return $this->original[$key] ?? null;
    }
}
