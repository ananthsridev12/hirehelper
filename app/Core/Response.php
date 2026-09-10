<?php

namespace App\Core;

class Response
{
    public static function view(string $view, array $data = [], ?string $layout = 'main'): void
    {
        extract($data, EXTR_SKIP);
        $viewPath = APP_ROOT . '/app/Views/' . $view . '.php';
        if (!is_file($viewPath)) {
            http_response_code(500);
            echo "View not found: {$view}";
            return;
        }

        if ($layout === null) {
            require $viewPath;
            return;
        }

        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        require APP_ROOT . '/app/Views/layouts/' . $layout . '.php';
    }

    public static function redirect(string $path): void
    {
        header('Location: ' . Url::to($path));
        exit;
    }

    public static function notFound(): void
    {
        http_response_code(404);
        self::view('errors/404', [], 'main');
    }

    public static function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
