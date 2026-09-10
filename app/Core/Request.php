<?php

namespace App\Core;

class Request
{
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

    public static function input(string $key, $default = null)
    {
        if (array_key_exists($key, $_POST)) {
            $value = $_POST[$key];
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
