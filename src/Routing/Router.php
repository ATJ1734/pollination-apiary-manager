<?php

declare(strict_types=1);

namespace App\Routing;

use App\Controllers\ErrorController;

/**
 * A small front-controller router. Each route carries its own allowed roles
 * so access control happens here, on every request, not just by hiding a
 * link in a template.
 */
final class Router
{
    /** @var list<array{method: string, pattern: string, handler: array, roles: list<string>}> */
    private array $routes = [];

    public function get(string $pattern, array $handler, array $roles = ['any']): void
    {
        $this->routes[] = ['method' => 'GET', 'pattern' => $pattern, 'handler' => $handler, 'roles' => $roles];
    }

    public function post(string $pattern, array $handler, array $roles = ['any']): void
    {
        $this->routes[] = ['method' => 'POST', 'pattern' => $pattern, 'handler' => $handler, 'roles' => $roles];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = rtrim(parse_url($uri, PHP_URL_PATH) ?: '/', '/');
        if ($path === '') {
            $path = '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->match($route['pattern'], $path);
            if ($params === null) {
                continue;
            }

            if (!$this->authorize($route['roles'])) {
                return;
            }

            [$class, $action] = $route['handler'];
            $controller = new $class();
            $controller->$action($params);

            return;
        }

        (new ErrorController())->notFound();
    }

    /** @return array<string,string>|null */
    private function match(string $pattern, string $path): ?array
    {
        $patternParts = explode('/', trim($pattern, '/'));
        $pathParts = explode('/', trim($path, '/'));

        if (count($patternParts) !== count($pathParts)) {
            return null;
        }

        $params = [];
        foreach ($patternParts as $i => $part) {
            if (str_starts_with($part, '{') && str_ends_with($part, '}')) {
                $params[substr($part, 1, -1)] = $pathParts[$i];
            } elseif ($part !== $pathParts[$i]) {
                return null;
            }
        }

        return $params;
    }

    /** @param list<string> $roles */
    private function authorize(array $roles): bool
    {
        if (in_array('guest', $roles, true)) {
            return true;
        }

        $user = current_user();

        if ($user === null) {
            header('Location: /login');
            return false;
        }

        if (in_array('any', $roles, true)) {
            return true;
        }

        if (!in_array($user['role'], $roles, true)) {
            (new ErrorController())->forbidden();
            return false;
        }

        return true;
    }
}
