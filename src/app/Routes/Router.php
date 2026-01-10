<?php
namespace App\Routes;
// imports built in php classes
use Closure; // allows for closures                             
use InvalidArgumentException; // allows for invalid arguments
use RuntimeException; // allows for runtime exceptions

class Router {
    private array $routes = [];
    private array $middleware = [];
    private string $currentGroup = '';
    private array $groupMiddleware = [];

    /**
     * Add a new route to the router
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $path URL path
     * @param array|Closure $handler Controller class and method or closure
     * @return self
     * @throws InvalidArgumentException
     */
    public function add(string $method, string $path, array|Closure $handler): self {
        if (!in_array($method, ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'])) {
            throw new InvalidArgumentException("Invalid HTTP method: {$method}");
        }

        // Combine group prefix with path
        if (!empty($this->currentGroup)) {
            $path = $this->currentGroup . '/' . ltrim($path, '/');
        }

        // Normalize path: remove trailing slash, collapse multiple slashes, ensure leading slash
        $path = '/' . trim($path, '/');
        $path = preg_replace('#/+#', '/', $path); // Replace multiple slashes with single slash

        // Handle root path
        if ($path === '/') {
            $path = '';
        }

        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middleware' => array_merge($this->middleware, $this->groupMiddleware)
        ];

        return $this;
    }

    /**
     * Add a GET route
     * @param string $path
     * @param array|Closure $handler
     * @return self
     */
    public function get(string $path, array|Closure $handler): self {
        return $this->add('GET', $path, $handler);
    }

    /**
     * Add a POST route
     * @param string $path
     * @param array|Closure $handler
     * @return self
     */
    public function post(string $path, array|Closure $handler): self {
        return $this->add('POST', $path, $handler);
    }

    /**
     * Add a PUT route
     * @param string $path
     * @param array|Closure $handler
     * @return self
     */
    public function put(string $path, array|Closure $handler): self {
        return $this->add('PUT', $path, $handler);
    }

    /**
     * Add a DELETE route
     * @param string $path
     * @param array|Closure $handler
     * @return self
     */
    public function delete(string $path, array|Closure $handler): self {
        return $this->add('DELETE', $path, $handler);
    }

    /**
     * Add middleware to all routes
     * @param array|Closure $middleware
     * @return self
     */
    public function middleware(array|Closure $middleware): self {
        $this->middleware = array_merge($this->middleware, (array)$middleware);
        return $this;
    }

    /**
     * Create a route group with common prefix and middleware
     * @param string $prefix
     * @param array $middleware
     * @param Closure $callback
     * @return self
     */
    public function group(string $prefix, array $middleware, Closure $callback): self {
        $previousGroup = $this->currentGroup;
        $previousMiddleware = $this->groupMiddleware;

        // Handle empty prefix - don't add extra slash
        if (!empty($prefix)) {
            $this->currentGroup = $previousGroup . '/' . ltrim($prefix, '/');
        } else {
            $this->currentGroup = $previousGroup;
        }

        $this->groupMiddleware = array_merge($this->groupMiddleware, $middleware);

        $callback($this);

        $this->currentGroup = $previousGroup;
        $this->groupMiddleware = $previousMiddleware;

        return $this;
    }

    /**
     * Match the current request to a route
     * @param string $method
     * @param string $uri
     * @return array|null
     */
    public function match(string $method, string $uri): ?array {
        $uri = parse_url($uri, PHP_URL_PATH);

        // Normalize URI the same way as routes
        $uri = '/' . trim($uri, '/');
        $uri = preg_replace('#/+#', '/', $uri);
        if ($uri === '/') {
            $uri = '';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = $this->convertPathToRegex($route['path']);
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches); // Remove the full match
                return [
                    'handler' => $route['handler'],
                    'params' => $matches,
                    'middleware' => $route['middleware']
                ];
            }
        }

        return null;
    }

    /**
     * Convert route path to regex pattern
     * @param string $path
     * @return string
     */
    private function convertPathToRegex(string $path): string {
        return '#^' . preg_replace('#\{([a-zA-Z0-9_]+)\}#', '([^/]+)', $path) . '$#';
    }

    /**
     * Dispatch the request to the appropriate handler
     * @param string $method
     * @param string $uri
     * @return mixed
     * @throws RuntimeException
     */
    public function dispatch(string $method, string $uri) {
        $match = $this->match($method, $uri);
        
        if (!$match) {
            throw new RuntimeException("No route found for {$method} {$uri}", 404);
        }

        // Execute middleware
        foreach ($match['middleware'] as $middleware) {
            if (is_callable($middleware)) {
                $middleware();
            }
        }

        // Execute handler
        $handler = $match['handler'];
        if (is_callable($handler)) {
            return $handler(...$match['params']);
        }

        if (is_array($handler)) {
            [$class, $method] = $handler;
            $instance = new $class();
            return $instance->$method(...$match['params']);
        }

        throw new RuntimeException("Invalid route handler", 500);
    }
}