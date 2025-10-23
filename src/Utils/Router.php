<?php

namespace App\Utils;

class Router {
    private $routes = [];
    private $middlewares = [];

    public function get(string $path, $handler, $middleware = null) {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, $handler, $middleware = null) {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    public function put(string $path, $handler, $middleware = null) {
        $this->addRoute('PUT', $path, $handler, $middleware);
    }

    public function delete(string $path, $handler, $middleware = null) {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    private function addRoute(string $method, string $path, $handler, $middlewares = null) {
        $path = str_replace([':int', ':slug'], ['([0-9]+)', '([a-zA-Z0-9_\-]+)'], $path);
        
        $this->routes[$method][$path] = $handler;
        if ($middlewares) {
            // Convierte el middleware a un array si no lo es, para manejar múltiples
            $this->middlewares[$method][$path] = is_array($middlewares) ? $middlewares : [$middlewares];
        }
    }

    public function dispatch(string $uri, string $method) {
        $path = parse_url($uri, PHP_URL_PATH);

        // Normalizar ruta
        $path = rtrim($path ?? '', '/');
        if ($path === '') $path = '/';

        foreach ($this->routes[$method] as $route => $handler) {
            $pattern = "#^$route$#";
            if (preg_match($pattern, $path, $matches)) {
                array_shift($matches);
                $this->execute($method, $route, $matches);
                return;
            }
        }

        http_response_code(404);
        echo "404 - Página no encontrada";
    }

    private function execute(string $method, string $route, array $params) {
        try {
            // Ejecuta todos los middlewares si existen
            if (isset($this->middlewares[$method][$route])) {
                foreach ($this->middlewares[$method][$route] as $middleware) {
                    if (!$middleware()) {
                        http_response_code(403);
                        echo "403 - Acceso denegado";
                        return;
                    }
                }
            }

            $handler = $this->routes[$method][$route];
            
            if ($handler instanceof \Closure) {
                call_user_func_array($handler, $params);
            } else {
                [$controller, $action] = $handler;
                $controllerInstance = new $controller();
                call_user_func_array([$controllerInstance, $action], $params);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo "500 - Error interno del servidor: " . $e->getMessage();
        }
    }
}