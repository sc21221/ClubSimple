<?php

declare(strict_types=1);

namespace Clubmanager\Core;

/**
 * Custom URL Router
 *
 * Dispatches HTTP requests to Controller actions with dynamic route parameter binding
 * and RBAC middleware enforcement.
 *
 * @package Clubmanager\Core
 * @author  Reinhard Schiessl
 */
class Router
{
    /**
     * @var array<string, array<int, array{pattern: string, regex: string, handler: array|callable, middleware: array<int, string>, paramNames: array<int, string>}>>
     */
    protected array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    /**
     * Register a GET route.
     *
     * @param string $path
     * @param array{0: class-string, 1: string}|callable $handler
     * @param array<int, string> $middleware
     * @return void
     */
    public function get(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    /**
     * Register a POST route.
     *
     * @param string $path
     * @param array{0: class-string, 1: string}|callable $handler
     * @param array<int, string> $middleware
     * @return void
     */
    public function post(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    /**
     * Internal route compiler.
     *
     * @param string $method
     * @param string $path
     * @param array|callable $handler
     * @param array<int, string> $middleware
     * @return void
     */
    protected function addRoute(string $method, string $path, array|callable $handler, array $middleware): void
    {
        // Extract parameter names like {id}
        preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $path, $matches);
        $paramNames = $matches[1] ?? [];

        // Convert {id} to regex pattern
        $regex = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $path);
        $regex = '#^' . $regex . '$#';

        $this->routes[$method][] = [
            'pattern' => $path,
            'regex' => $regex,
            'handler' => $handler,
            'middleware' => $middleware,
            'paramNames' => $paramNames,
        ];
    }

    /**
     * Dispatch the current request.
     *
     * @param Request $request
     * @return void
     */
    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $path = $request->path();

        $routesForMethod = $this->routes[$method] ?? [];

        foreach ($routesForMethod as $route) {
            if (preg_match($route->regex ?? $route['regex'], $path, $matches)) {
                array_shift($matches); // Remove full match

                // Map matched values to param names
                $params = [];
                $paramNames = $route['paramNames'];
                foreach ($paramNames as $idx => $name) {
                    $params[$name] = $matches[$idx] ?? null;
                }

                // Execute middleware pipeline
                if (!$this->runMiddleware($route['middleware'], $request)) {
                    return;
                }

                // Execute handler
                $handler = $route['handler'];
                if (is_callable($handler)) {
                    call_user_func($handler, $request, ...array_values($params));
                    return;
                }

                if (is_array($handler) && count($handler) === 2) {
                    [$controllerClass, $methodName] = $handler;
                    $controller = new $controllerClass();
                    call_user_func_array([$controller, $methodName], array_merge([$request], $params));
                    return;
                }
            }
        }

        // No route matched -> 404
        $this->renderError(404, 'Seite nicht gefunden', 'Die angeforderte URL wurde auf dem Server nicht gefunden.');
    }

    /**
     * Run middleware chain.
     *
     * @param array<int, string> $middleware
     * @param Request $request
     * @return bool
     */
    protected function runMiddleware(array $middleware, Request $request): bool
    {
        foreach ($middleware as $mw) {
            switch ($mw) {
                case 'auth':
                    if (!Auth::check()) {
                        Session::flash('warning', __('session_expired'));
                        Response::redirect('/login');
                        return false;
                    }
                    break;

                case 'admin':
                    if (!Auth::check() || !Auth::isAdmin()) {
                        $this->renderError(403, 'Zugriff verweigert', __('unauthorized'));
                        return false;
                    }
                    break;

                case 'guest':
                    if (Auth::check()) {
                        Response::redirect('/dashboard');
                        return false;
                    }
                    break;
            }
        }
        return true;
    }

    /**
     * Render an HTTP error view.
     *
     * @param int $code
     * @param string $title
     * @param string $message
     * @return void
     */
    protected function renderError(int $code, string $title, string $message): void
    {
        http_response_code($code);
        $errorFile = dirname(__DIR__, 2) . "/views/errors/{$code}.php";
        if (file_exists($errorFile)) {
            require $errorFile;
        } else {
            echo "<h1>{$code} - {$title}</h1><p>{$message}</p><p><a href='/'>Zur Startseite</a></p>";
        }
        exit;
    }
}
