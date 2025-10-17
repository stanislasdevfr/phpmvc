<?php

namespace App\Core;

class Router
{
    private array $routes = [];
    private string $basePath = '';

    public function __construct(string $basePath = '')
    {
        $this->basePath = rtrim($basePath, '/');
    }

    /**
     * Add a GET route
     */
    public function get(string $path, string $controller, string $method): void
    {
        $this->addRoute('GET', $path, $controller, $method);
    }

    /**
     * Add a POST route
     */
    public function post(string $path, string $controller, string $method): void
    {
        $this->addRoute('POST', $path, $controller, $method);
    }

    /**
     * Add a PUT route
     */
    public function put(string $path, string $controller, string $method): void
    {
        $this->addRoute('PUT', $path, $controller, $method);
    }

    /**
     * Add a DELETE route
     */
    public function delete(string $path, string $controller, string $method): void
    {
        $this->addRoute('DELETE', $path, $controller, $method);
    }

    /**
     * Add a route for any HTTP method
     */
    private function addRoute(string $httpMethod, string $path, string $controller, string $method): void
    {
        $path = $this->basePath . $path;
        
        $this->routes[] = [
            'http_method' => $httpMethod,
            'path' => $path,
            'controller' => $controller,
            'method' => $method
        ];
    }

    /**
     * Dispatch the current request
     */
    public function dispatch(): void
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Handle PUT and DELETE via POST with _method
        if ($requestMethod === 'POST' && isset($_POST['_method'])) {
            $requestMethod = strtoupper($_POST['_method']);
        }

        foreach ($this->routes as $route) {
            $pattern = $this->convertPathToRegex($route['path']);
            
            if ($route['http_method'] === $requestMethod && preg_match($pattern, $requestUri, $matches)) {
                array_shift($matches); // Remove full match
                
                $this->callController($route['controller'], $route['method'], $matches);
                return;
            }
        }

        // No route found
        http_response_code(404);
        echo json_encode(['error' => 'Route not found']);
    }

    /**
     * Convert route path to regex pattern
     */
    private function convertPathToRegex(string $path): string
    {
        // Replace {id} with (\d+), {slug} with ([a-z0-9-]+), etc.
        $pattern = preg_replace('/\{id\}/', '(\d+)', $path);
        $pattern = preg_replace('/\{([a-z]+)\}/', '([^/]+)', $pattern);
        
        return '#^' . $pattern . '$#';
    }

    /**
     * Call the controller method
     */
    private function callController(string $controllerClass, string $method, array $params): void
    {
        $fullControllerClass = "App\\Controller\\{$controllerClass}";
        
        if (!class_exists($fullControllerClass)) {
            http_response_code(500);
            echo json_encode(['error' => 'Controller not found']);
            return;
        }

        $controller = new $fullControllerClass();

        if (!method_exists($controller, $method)) {
            http_response_code(500);
            echo json_encode(['error' => 'Method not found']);
            return;
        }

        call_user_func_array([$controller, $method], $params);
    }

    /**
     * Generate CRUD routes for a resource
     */
    public function resource(string $name, string $controller): void
    {
        $nameLower = strtolower($name);
        
        // GET /users - index
        $this->get("/{$nameLower}", $controller, 'index');
        
        // GET /users/{id} - show
        $this->get("/{$nameLower}/{id}", $controller, 'show');
        
        // GET /users/create - create form
        $this->get("/{$nameLower}/create", $controller, 'create');
        
        // POST /users - store
        $this->post("/{$nameLower}", $controller, 'store');
        
        // GET /users/{id}/edit - edit form
        $this->get("/{$nameLower}/{id}/edit", $controller, 'edit');
        
        // POST /users/{id} with _method=PUT - update
        $this->put("/{$nameLower}/{id}", $controller, 'update');
        
        // POST /users/{id} with _method=DELETE - delete
        $this->delete("/{$nameLower}/{id}", $controller, 'delete');
    }
}