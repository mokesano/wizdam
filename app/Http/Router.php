<?php

declare(strict_types=1);

namespace Wizdam\Http;

use Closure;

/**
 * Router sederhana dengan support pattern matching dan middleware.
 */
class Router
{
    private array $routes = [];
    private array $globalMiddleware = [];

    /**
     * Register route GET
     */
    public function get(string $path, callable|array $handler): self
    {
        return $this->addRoute('GET', $path, $handler);
    }

    /**
     * Register route POST
     */
    public function post(string $path, callable|array $handler): self
    {
        return $this->addRoute('POST', $path, $handler);
    }

    /**
     * Register route untuk semua method
     */
    public function any(string $path, callable|array $handler): self
    {
        return $this->addRoute('*', $path, $handler);
    }

    /**
     * Tambahkan middleware global
     */
    public function use(Closure $middleware): self
    {
        $this->globalMiddleware[] = $middleware;
        return $this;
    }

    /**
     * Internal: tambahkan route ke daftar
     */
    private function addRoute(string $method, string $path, callable|array $handler): self
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $this->normalizePath($path),
            'handler' => $handler,
            'pattern' => $this->convertToRegex($path),
        ];
        return $this;
    }

    /**
     * Normalize path: pastikan dimulai dengan /
     */
    private function normalizePath(string $path): string
    {
        return '/' . ltrim($path, '/');
    }

    /**
     * Convert path pattern ke regex (support {param} dan {id:\d+})
     */
    private function convertToRegex(string $path): string
    {
        // Ganti {param} dengan capture group
        $pattern = preg_replace_callback('/\{(\w+)(?::([^}]+))?\}/', function ($matches) {
            $name = $matches[1];
            $regex = $matches[2] ?? '[^/]+';
            return "(?P<$name>$regex)";
        }, $path);

        return '#^' . str_replace('/', '\/', $pattern) . '$#';
    }

    /**
     * Dispatch request ke handler yang sesuai
     */
    public function dispatch(Request $request): Response
    {
        $path = $this->normalizePath($request->path);

        foreach ($this->routes as $route) {
            if ($route['method'] !== '*' && $route['method'] !== $request->method) {
                continue;
            }

            if (preg_match($route['pattern'], $path, $matches)) {
                // Filter hanya named parameters
                $params = array_filter($matches, fn($key) => is_string($key), ARRAY_FILTER_USE_KEY);

                // Jalankan global middleware
                foreach ($this->globalMiddleware as $middleware) {
                    $result = $middleware($request, $params);
                    if ($result instanceof Response) {
                        return $result;
                    }
                }

                // Handle controller@method atau closure
                return $this->callHandler($route['handler'], $request, $params);
            }
        }

        return Response::notFound('Route not found: ' . $request->path);
    }

    /**
     * Call handler (bisa closure, array [controller, method], atau string controller@method)
     */
    private function callHandler(callable|array $handler, Request $request, array $params): Response
    {
        // Array: [$instance, 'method']
        if (is_array($handler) && isset($handler[0]) && isset($handler[1])) {
            $instance = $handler[0];
            $method = $handler[1];
            return $instance->$method($request, ...array_values($params));
        }

        // Closure atau callable langsung
        if ($handler instanceof Closure || is_callable($handler)) {
            return $handler($request, ...array_values($params));
        }

        throw new \InvalidArgumentException('Invalid handler type');
    }
}
