<?php

declare(strict_types=1);

namespace App\Controllers;

use Throwable;

final class ErrorController extends Controller
{
    public function notFound(): void
    {
        http_response_code(404);
        $this->view('errors/404');
    }

    public function forbidden(): void
    {
        http_response_code(403);
        $this->view('errors/403');
    }

    public function serverError(Throwable $e): void
    {
        http_response_code(500);
        $this->view('errors/500', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'debug' => (bool) config('app.debug'),
        ]);
    }
}
