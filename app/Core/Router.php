<?php

namespace App\Core;

class Router
{
    protected array $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'DELETE' => [],
    ];

    protected $notFoundHandler;

    /**
     * Register GET route
     */
    public function get(string $path, $handler)
    {
        $this->addRoute('GET', $path, $handler);
    }

    /**
     * Register POST route
     */
    public function post(string $path, $handler)
    {
        $this->addRoute('POST', $path, $handler);
    }

    /**
     * Register a route for given HTTP method
     */
    public function addRoute(string $method, string $path, $handler)
    {
        $path = '/' . trim($path, '/');
        $regex = $this->convertToRegex($path);
        $this->routes[$method][] = [
            'path' => $path,
            'regex' => $regex,
            'handler' => $handler,
        ];
    }

    /**
     * Convert a route pattern like /product/{slug} to a regex with named captures
     */
    protected function convertToRegex(string $path): string
    {
        $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_-]*)\}/', function ($m) {
            return '(?P<' . $m[1] . '>[^/]+)';
        }, $path);

        return '#^' . $regex . '/?$#';
    }

    /**
     * Dispatch the current request to the matching route
     */
    public function dispatch(?string $uri = null, ?string $method = null)
    {
        $method = $method ?? ($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = $uri ?? ($_SERVER['REQUEST_URI'] ?? '/');
        $uri = parse_url($uri, PHP_URL_PATH) ?: '/';
        $uri = '/' . trim($uri, '/');

        if (!isset($this->routes[$method])) {
            return $this->handleNotFound();
        }

        foreach ($this->routes[$method] as $route) {
            if (preg_match($route['regex'], $uri, $matches)) {
                $params = [];
                foreach ($matches as $key => $val) {
                    if (is_string($key)) {
                        $params[$key] = $val;
                    }
                }

                return $this->invokeHandler($route['handler'], $params);
            }
        }

        return $this->handleNotFound();
    }

    protected function invokeHandler($handler, array $params = [])
    {
        if (is_callable($handler)) {
            return call_user_func_array($handler, array_values($params));
        }

        if (is_string($handler)) {
            if (strpos($handler, '@') !== false) {
                [$controller, $method] = explode('@', $handler, 2);
                $controller = str_replace(['/', '\\'], '\\', $controller);
                $fqcn = $this->qualifyController($controller);

                if (!class_exists($fqcn)) {
                    throw new \Exception("Controller {$fqcn} not found");
                }

                $instance = new $fqcn();

                if (!method_exists($instance, $method)) {
                    throw new \Exception("Method {$method} not found in controller {$fqcn}");
                }

                return call_user_func_array([$instance, $method], array_values($params));
            }

            // Fallback to function name
            if (function_exists($handler)) {
                return call_user_func_array($handler, array_values($params));
            }
        }

        throw new \Exception('Invalid route handler');
    }

    protected function qualifyController(string $controller): string
    {
        if (strpos($controller, 'App\\') === 0) {
            return $controller;
        }

        return 'App\\Controllers\\' . trim($controller, '\\');
    }

    /**
     * Set a custom 404 handler (callable or view name)
     */
    public function setNotFoundHandler($handler)
    {
        $this->notFoundHandler = $handler;
    }

    protected function handleNotFound()
    {
        http_response_code(404);

        if ($this->notFoundHandler) {
            if (is_callable($this->notFoundHandler)) {
                return call_user_func($this->notFoundHandler);
            }

            if (is_string($this->notFoundHandler)) {
                // assume it's a view name
                return Views::render($this->notFoundHandler, [], null);
            }
        }

        echo '<h1>404 Not Found</h1>';
        return null;
    }

    /**
     * Register route to respond to any common HTTP methods
     */
    public function any(string $path, $handler)
    {
        foreach (['GET', 'POST', 'PUT', 'DELETE'] as $m) {
            $this->addRoute($m, $path, $handler);
        }
    }
}
