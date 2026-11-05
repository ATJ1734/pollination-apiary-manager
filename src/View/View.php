<?php

declare(strict_types=1);

namespace App\View;

/** Renders a template inside the shared layout. One shared layout, per the brief. */
final class View
{
    public static function render(string $template, array $data = []): void
    {
        $templatesPath = dirname(__DIR__, 2) . '/templates';

        $data += [
            'company' => config('company'),
            'appName' => config('app.name'),
            'user' => current_user(),
            'flashes' => $_SESSION['_flash'] ?? [],
        ];
        unset($_SESSION['_flash']);

        $content = self::renderFile("{$templatesPath}/{$template}.php", $data);

        $layoutData = $data + ['content' => $content];

        echo self::renderFile("{$templatesPath}/layout/base.php", $layoutData);
    }

    /** Renders a template with no layout wrapper, e.g. the print view and JSON partials. */
    public static function renderBare(string $template, array $data = []): string
    {
        $templatesPath = dirname(__DIR__, 2) . '/templates';

        return self::renderFile("{$templatesPath}/{$template}.php", $data);
    }

    private static function renderFile(string $path, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require $path;

        return (string) ob_get_clean();
    }
}
