<?php
declare(strict_types=1);

namespace App;

class Router
{
    private array $routes = [];
    private array $middleware = [];
    private string $prefix = '';

    public function group(string $prefix, callable $callback, array $middleware = []): void
    {
        $previousPrefix = $this->prefix;
        $this->prefix = $previousPrefix . $prefix;
        $callback($this);
        $this->prefix = $previousPrefix;
    }

    public function get(string $path, array $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    public function put(string $path, array $handler, array $middleware = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middleware);
    }

    public function delete(string $path, array $handler, array $middleware = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    public function match(array $methods, string $path, array $handler, array $middleware = []): void
    {
        foreach ($methods as $method) {
            $this->addRoute(strtoupper($method), $path, $handler, $middleware);
        }
    }

    private function addRoute(string $method, string $path, array $handler, array $middleware): void
    {
        $fullPath = $this->prefix . $path;
        $this->routes[] = [
            'method'     => $method,
            'path'       => $fullPath,
            'handler'    => $handler,
            'middleware'  => $middleware,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = $this->buildPattern($route['path']);

            if (preg_match($pattern, $uri, $matches)) {
                // Run middleware
                foreach ($route['middleware'] as $mw) {
                    $middlewareClass = "App\\Middleware\\{$mw}";
                    if (class_exists($middlewareClass)) {
                        $middlewareInstance = new $middlewareClass();
                        $result = $middlewareInstance->handle();
                        if ($result !== true) {
                            return; // Middleware stopped execution
                        }
                    }
                }

                // Extract route parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Call handler
                [$controllerClass, $action] = $route['handler'];
                $controller = new $controllerClass();
                call_user_func_array([$controller, $action], $params);
                return;
            }
        }

        // No route matched — try legacy file-based routing
        $this->fallback($uri);
    }

    private function buildPattern(string $path): string
    {
        // Convert {param} to named capture groups
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '/?$#';
    }

    private function fallback(string $uri): void
    {
        // Map URI to filesystem path for legacy pages
        $legacyPath = dirname(__DIR__) . $uri;

        // Try adding .php extension
        if (is_file($legacyPath)) {
            // Serve the legacy file directly (backward compat)
            return;
        }

        if (is_file($legacyPath . '.php')) {
            return;
        }

        // 404
        http_response_code(404);
        if (is_file(dirname(__DIR__) . '/errors/404.php')) {
            require dirname(__DIR__) . '/errors/404.php';
        } else {
            echo '404 Not Found';
        }
    }
}
