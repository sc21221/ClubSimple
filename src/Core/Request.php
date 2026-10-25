<?php

declare(strict_types=1);

namespace Clubmanager\Core;

/**
 * HTTP Request Representation
 *
 * @package Clubmanager\Core
 * @author  Reinhard Schiessl
 */
class Request
{
    /**
     * @var array<string, mixed>
     */
    protected array $query;

    /**
     * @var array<string, mixed>
     */
    protected array $post;

    /**
     * @var array<string, mixed>
     */
    protected array $server;

    public function __construct()
    {
        $this->query = $_GET;
        $this->post = $_POST;
        $this->server = $_SERVER;

        // If JSON payload was submitted
        $contentType = $this->server['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            $data = json_decode($raw, true);
            if (is_array($data)) {
                $this->post = array_merge($this->post, $data);
            }
        }
    }

    /**
     * Get HTTP request method (GET, POST, PUT, DELETE).
     *
     * @return string
     */
    public function method(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Get the sanitized request URI path without query string.
     *
     * @return string
     */
    public function path(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        
        // Remove trailing slash if not root
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }
        return $path;
    }

    /**
     * Get query string parameter.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }

    /**
     * Get POST parameter.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function post(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->post;
        }
        return $this->post[$key] ?? $default;
    }

    /**
     * Get input from POST or GET.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function input(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return array_merge($this->query, $this->post);
        }
        return $this->post[$key] ?? ($this->query[$key] ?? $default);
    }

    /**
     * Return all input parameters merged.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return array_merge($this->query, $this->post);
    }

    /**
     * Get client IP address.
     *
     * @return string
     */
    public function ip(): string
    {
        return $this->server['HTTP_X_FORWARDED_FOR'] ?? ($this->server['REMOTE_ADDR'] ?? '127.0.0.1');
    }

    /**
     * Determine if request is AJAX.
     *
     * @return bool
     */
    public function isAjax(): bool
    {
        return (!empty($this->server['HTTP_X_REQUESTED_WITH']) &&
            strtolower($this->server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
            (isset($this->server['HTTP_ACCEPT']) && str_contains($this->server['HTTP_ACCEPT'], 'application/json'));
    }
}
