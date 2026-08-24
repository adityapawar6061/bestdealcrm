<?php
/**
 * Simple Router
 * Supports GET, POST, PUT, DELETE methods with middleware
 */

class Router
{
    private array $routes = [];
    private array $middlewares = [];
    private string $prefix = '';

    /**
     * Set a route prefix for a group
     */
    public function prefix(string $prefix, callable $callback): void
    {
        $previousPrefix = $this->prefix;
        $this->prefix = $previousPrefix . $prefix;
        $callback($this);
        $this->prefix = $previousPrefix;
    }

    /**
     * Apply middleware to a group
     */
    public function middleware(array $middlewareNames, callable $callback): void
    {
        $previousMiddleware = $this->middlewares;
        $this->middlewares = array_merge($this->middlewares, $middlewareNames);
        $callback($this);
        $this->middlewares = $previousMiddleware;
    }

    public function get(string $path, string $controller, string $method): void
    {
        $this->addRoute('GET', $path, $controller, $method);
    }

    public function post(string $path, string $controller, string $method): void
    {
        $this->addRoute('POST', $path, $controller, $method);
    }

    public function put(string $path, string $controller, string $method): void
    {
        $this->addRoute('PUT', $path, $controller, $method);
    }

    public function delete(string $path, string $controller, string $method): void
    {
        $this->addRoute('DELETE', $path, $controller, $method);
    }

    private function addRoute(string $httpMethod, string $path, string $controller, string $method): void
    {
        $fullPath = $this->prefix . $path;
        $this->routes[] = [
            'method'     => $httpMethod,
            'path'       => $fullPath,
            'controller' => $controller,
            'action'     => $method,
            'middleware'  => $this->middlewares,
        ];
    }

    /**
     * Match a request to a route
     */
    public function dispatch(): void
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        
        // Handle method spoofing for PUT/DELETE via POST
        if ($requestMethod === 'POST' && isset($_POST['_method'])) {
            $requestMethod = strtoupper($_POST['_method']);
        }

        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $requestUri = rtrim($requestUri, '/') ?: '/';

        foreach ($this->routes as $route) {
            $routePath = rtrim($route['path'], '/') ?: '/';
            
            // Exact match first
            if ($route['method'] === $requestMethod && $routePath === $requestUri) {
                $this->executeRoute($route, []);
                return;
            }

            // Parameterized match
            if ($route['method'] === $requestMethod) {
                $params = $this->matchRoute($routePath, $requestUri);
                if ($params !== false) {
                    $this->executeRoute($route, $params);
                    return;
                }
            }
        }

        // 404 Not Found
        http_response_code(404);
        require VIEWS_PATH . '/errors/404.php';
    }

    /**
     * Match a route pattern with parameters
     */
    private function matchRoute(string $pattern, string $uri)
    {
        $patternParts = explode('/', trim($pattern, '/'));
        $uriParts = explode('/', trim($uri, '/'));

        if (count($patternParts) !== count($uriParts)) {
            return false;
        }

        $params = [];
        foreach ($patternParts as $i => $part) {
            if (strpos($part, '{') === 0 && strpos($part, '}') === strlen($part) - 1) {
                $paramName = substr($part, 1, -1);
                $params[$paramName] = $uriParts[$i];
            } elseif ($part !== $uriParts[$i]) {
                return false;
            }
        }

        return $params;
    }

    /**
     * Execute a matched route
     */
    private function executeRoute(array $route, array $params): void
    {
        // Run middleware
        foreach ($route['middleware'] as $middlewareName) {
            $middlewareClass = 'Middleware\\' . $middlewareName;
            if (class_exists($middlewareClass)) {
                $middleware = new $middlewareClass();
                if (!$middleware->handle()) {
                    return; // Middleware halted the request
                }
            }
        }

        // Resolve controller
        $controllerClass = 'Controllers\\' . $route['controller'];
        
        if (!class_exists($controllerClass)) {
            http_response_code(500);
            throw new \RuntimeException("Controller not found: {$route['controller']}");
        }

        $controller = new $controllerClass();
        $action = $route['action'];

        if (!method_exists($controller, $action)) {
            http_response_code(500);
            throw new \RuntimeException("Method not found: {$controllerClass}::{$action}");
        }

        call_user_func_array([$controller, $action], $params);
    }
}
