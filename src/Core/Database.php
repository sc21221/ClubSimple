<?php

declare(strict_types=1);

namespace Clubmanager\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Database Manager & PDO Wrapper
 *
 * Provides a lightweight, reliable database interface supporting both
 * MySQL/MariaDB and SQLite with transaction support and prepared statements.
 *
 * @package Clubmanager\Core
 * @author  Reinhard Schiessl
 */
class Database
{
    /**
     * @var PDO|null Singleton PDO instance
     */
    protected static ?PDO $instance = null;

    /**
     * @var string Active driver name ('mysql' or 'sqlite')
     */
    protected static string $driver = 'sqlite';

    /**
     * Get or create the singleton PDO connection instance.
     *
     * @return PDO
     * @throws RuntimeException
     */
    public static function getConnection(): PDO
    {
        if (static::$instance !== null) {
            return static::$instance;
        }

        $config = require dirname(__DIR__, 2) . '/config/database.php';
        $default = $config['default'] ?? 'sqlite';
        static::$driver = $default;
        $connConfig = $config['connections'][$default] ?? [];

        try {
            if ($default === 'sqlite') {
                $dbFile = $connConfig['database'];
                $dbDir = dirname($dbFile);
                if (!is_dir($dbDir)) {
                    mkdir($dbDir, 0755, true);
                }

                $isNew = !file_exists($dbFile) || filesize($dbFile) === 0;

                static::$instance = new PDO(
                    'sqlite:' . $dbFile,
                    null,
                    null,
                    $connConfig['options'] ?? []
                );

                // Enable foreign keys in SQLite
                static::$instance->exec('PRAGMA foreign_keys = ON;');

                // If SQLite database file is newly created, initialize schema and seed data
                if ($isNew) {
                    \Clubmanager\Database\DatabaseInitializer::initialize(static::$instance, 'sqlite');
                }
            } elseif ($default === 'mysql') {
                $dsn = sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                    $connConfig['host'],
                    $connConfig['port'],
                    $connConfig['database'],
                    $connConfig['charset'] ?? 'utf8mb4'
                );

                static::$instance = new PDO(
                    $dsn,
                    $connConfig['username'],
                    $connConfig['password'],
                    $connConfig['options'] ?? []
                );
            } else {
                throw new RuntimeException("Unsupported database driver: {$default}");
            }

            Logger::debug("Database connected successfully using driver [{$default}].");
            return static::$instance;
        } catch (PDOException $e) {
            Logger::error("Database connection failure: " . $e->getMessage(), [
                'driver' => $default,
                'code' => $e->getCode(),
            ]);

            // If MySQL failed and we are in development, try SQLite fallback
            if ($default === 'mysql' && (getenv('APP_ENV') ?: 'development') === 'development') {
                Logger::warning("Falling back to SQLite database...");
                $sqliteConfig = $config['connections']['sqlite'];
                $dbFile = $sqliteConfig['database'];
                $dbDir = dirname($dbFile);
                if (!is_dir($dbDir)) {
                    mkdir($dbDir, 0755, true);
                }
                $isNew = !file_exists($dbFile) || filesize($dbFile) === 0;
                static::$driver = 'sqlite';
                static::$instance = new PDO('sqlite:' . $dbFile, null, null, $sqliteConfig['options'] ?? []);
                static::$instance->exec('PRAGMA foreign_keys = ON;');
                if ($isNew) {
                    \Clubmanager\Database\DatabaseInitializer::initialize(static::$instance, 'sqlite');
                }
                return static::$instance;
            }

            throw new RuntimeException("Database connection error: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Get the active driver name ('mysql' or 'sqlite').
     *
     * @return string
     */
    public static function getDriver(): string
    {
        return static::$driver;
    }

    /**
     * Execute a SQL query with parameters and return all rows.
     *
     * @param string $sql
     * @param array<int|string, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = static::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Execute a SQL query with parameters and return a single row.
     *
     * @param string $sql
     * @param array<int|string, mixed> $params
     * @return array<string, mixed>|null
     */
    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = static::getConnection()->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Execute a SQL statement (INSERT, UPDATE, DELETE) with parameters.
     *
     * @param string $sql
     * @param array<int|string, mixed> $params
     * @return int Affected rows
     */
    public static function execute(string $sql, array $params = []): int
    {
        $stmt = static::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Get the last inserted auto-increment ID.
     *
     * @param string|null $sequenceName
     * @return string
     */
    public static function lastInsertId(?string $sequenceName = null): string
    {
        return static::getConnection()->lastInsertId($sequenceName);
    }

    /**
     * Begin a database transaction.
     *
     * @return bool
     */
    public static function beginTransaction(): bool
    {
        return static::getConnection()->beginTransaction();
    }

    /**
     * Commit the active database transaction.
     *
     * @return bool
     */
    public static function commit(): bool
    {
        return static::getConnection()->commit();
    }

    /**
     * Roll back the active database transaction.
     *
     * @return bool
     */
    public static function rollBack(): bool
    {
        return static::getConnection()->rollBack();
    }
}
