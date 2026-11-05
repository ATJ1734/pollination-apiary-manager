<?php

declare(strict_types=1);

namespace App\Controllers;

use App\View\View;

abstract class Controller
{
    protected function view(string $template, array $data = []): void
    {
        View::render($template, $data);
    }

    protected function redirect(string $path): never
    {
        header('Location: ' . $path);
        exit;
    }

    protected function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    /** @return array<string,mixed> */
    protected function allInput(): array
    {
        return $_POST;
    }

    protected function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    protected function rememberOld(array $input): void
    {
        $_SESSION['_old'] = $input;
    }

    protected function forgetOld(): void
    {
        unset($_SESSION['_old']);
    }

    /** Every POST handler calls this first. Aborts the request with a 419-style flash on mismatch. */
    protected function requireCsrf(): void
    {
        $token = $this->input('_csrf', '');
        if (!is_string($token) || $token === '' || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            $this->flash('error', 'Your session expired. Please try again.');
            $this->redirect($_SERVER['HTTP_REFERER'] ?? '/dashboard');
        }
    }

    protected function requireRole(array $roles): void
    {
        $role = user_role();
        if ($role === null || !in_array($role, $roles, true)) {
            (new ErrorController())->forbidden();
            exit;
        }
    }

    protected function currentUserId(): int
    {
        return (int) (current_user()['id'] ?? 0);
    }
}
