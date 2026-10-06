<?php

namespace App\Core;

class Request
{
    private static ?array $jsonBody = null;

    public static function method(): string
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }
        return $method;
    }

    public static function path(): string
    {
        $path = $_GET['r'] ?? '';
        $path = '/' . trim($path, '/');
        return $path === '/' ? '/' : rtrim($path, '/');
    }

    /**
     * The Flutter app sends a JSON body instead of form-encoded fields.
     * Parsed once and cached; falls back to an empty array for anything
     * that isn't valid JSON so input() can check it unconditionally.
     */
    public static function json(): array
    {
        if (self::$jsonBody === null) {
            $raw = file_get_contents('php://input');
            $decoded = $raw !== false ? json_decode($raw, true) : null;
            self::$jsonBody = is_array($decoded) ? $decoded : [];
        }
        return self::$jsonBody;
    }

    public static function input(string $key, $default = null)
    {
        if (array_key_exists($key, $_POST)) {
            $value = $_POST[$key];
            return is_string($value) ? trim($value) : $value;
        }
        if (array_key_exists($key, self::json())) {
            $value = self::json()[$key];
            return is_string($value) ? trim($value) : $value;
        }
        return $default;
    }

    public static function all(): array
    {
        return $_POST;
    }

    public static function query(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    public static function file(string $key): ?array
    {
        if (!empty($_FILES[$key]) && $_FILES[$key]['error'] !== UPLOAD_ERR_NO_FILE) {
            return $_FILES[$key];
        }
        return null;
    }

    public static function isPost(): bool
    {
        return self::method() === 'POST';
    }
}
