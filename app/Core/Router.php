<?php

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $pattern, array $action): void
    {
        $this->add('GET', $pattern, $action);
    }

    public function post(string $pattern, array $action): void
    {
        $this->add('POST', $pattern, $action);
    }

    private function add(string $method, string $pattern, array $action): void
    {
        $pattern = '/' . trim($pattern, '/');
        $this->routes[] = compact('method', 'pattern', 'action');
    }

    public function dispatch(string $method, string $path): void
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            $params = $this->match($route['pattern'], $path);
            if ($params === null) {
                continue;
            }
            [$class, $methodName] = $route['action'];
            $controller = new $class();
            call_user_func_array([$controller, $methodName], $params);
            return;
        }
        Response::notFound();
    }

    private function match(string $pattern, string $path): ?array
    {
        $patternSegments = explode('/', trim($pattern, '/'));
        $pathSegments = explode('/', trim($path, '/'));

        if (count($patternSegments) !== count($pathSegments)) {
            return null;
        }

        $params = [];
        foreach ($patternSegments as $i => $segment) {
            if (preg_match('/^\{(\w+)\}$/', $segment, $m)) {
                if ($pathSegments[$i] === '') {
                    return null;
                }
                $params[] = urldecode($pathSegments[$i]);
            } elseif ($segment !== $pathSegments[$i]) {
                return null;
            }
        }
        return $params;
    }
}
