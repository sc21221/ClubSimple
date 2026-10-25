<?php

declare(strict_types=1);

namespace Clubmanager\Core;

/**
 * Structured Serilog-Compatible Logger
 *
 * Implements structured logging emitting Serilog CLEF (Compact Log Event Format)
 * JSON records as well as standard formatted lines for easy log ingestion.
 *
 * @package Clubmanager\Core
 * @author  Reinhard Schiessl
 */
class Logger
{
    public const DEBUG = 'Debug';
    public const INFO = 'Information';
    public const WARNING = 'Warning';
    public const ERROR = 'Error';
    public const FATAL = 'Fatal';

    /**
     * @var string Log file path
     */
    protected static string $logPath = '';

    /**
     * @var string Active minimum log level
     */
    protected static string $logLevel = 'Debug';

    /**
     * Priority map for log level filtering
     */
    protected static array $levelPriorities = [
        'debug' => 10,
        'info' => 20,
        'information' => 20,
        'warning' => 30,
        'warn' => 30,
        'error' => 40,
        'fatal' => 50,
    ];

    /**
     * Initialize the logger with configuration.
     *
     * @param string $path
     * @param string $level
     * @return void
     */
    public static function init(string $path, string $level = 'debug'): void
    {
        static::$logPath = $path;
        static::$logLevel = strtolower($level);

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * Log a debug level message.
     *
     * @param string $message
     * @param array<string, mixed> $context
     * @return void
     */
    public static function debug(string $message, array $context = []): void
    {
        static::log(self::DEBUG, $message, $context);
    }

    /**
     * Log an informational message.
     *
     * @param string $message
     * @param array<string, mixed> $context
     * @return void
     */
    public static function info(string $message, array $context = []): void
    {
        static::log(self::INFO, $message, $context);
    }

    /**
     * Log a warning message.
     *
     * @param string $message
     * @param array<string, mixed> $context
     * @return void
     */
    public static function warning(string $message, array $context = []): void
    {
        static::log(self::WARNING, $message, $context);
    }

    /**
     * Log an error message.
     *
     * @param string $message
     * @param array<string, mixed> $context
     * @return void
     */
    public static function error(string $message, array $context = []): void
    {
        static::log(self::ERROR, $message, $context);
    }

    /**
     * Core log emitter generating Serilog-compatible JSON structure.
     *
     * @param string $level
     * @param string $message
     * @param array<string, mixed> $context
     * @return void
     */
    public static function log(string $level, string $message, array $context = []): void
    {
        $currentPriority = static::$levelPriorities[strtolower($level)] ?? 20;
        $minPriority = static::$levelPriorities[static::$logLevel] ?? 10;

        if ($currentPriority < $minPriority) {
            return;
        }

        if (empty(static::$logPath)) {
            static::$logPath = dirname(__DIR__, 2) . '/storage/logs/clubmanager.log';
            $dir = dirname(static::$logPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }

        // Serilog Compact Log Event Format (CLEF) standard
        $event = [
            '@t' => (new \DateTimeImmutable())->format('Y-m-d\TH:i:s.u\Z'),
            '@l' => $level,
            '@mt' => $message,
            'App' => 'Clubmanager',
            'Version' => '1.7',
            'Environment' => getenv('APP_ENV') ?: 'production',
        ];

        // Enrich with user / request context if available
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user_id'])) {
            $event['UserId'] = $_SESSION['user_id'];
            $event['UserEmail'] = $_SESSION['user_email'] ?? null;
        }

        if (!empty($_SERVER['REMOTE_ADDR'])) {
            $event['ClientIp'] = $_SERVER['REMOTE_ADDR'];
        }

        if (!empty($_SERVER['REQUEST_URI'])) {
            $event['RequestPath'] = $_SERVER['REQUEST_URI'];
            $event['RequestMethod'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        }

        foreach ($context as $key => $val) {
            $event[$key] = $val;
        }

        $jsonLine = json_encode($event, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
        file_put_contents(static::$logPath, $jsonLine, FILE_APPEND | LOCK_EX);
    }
}
