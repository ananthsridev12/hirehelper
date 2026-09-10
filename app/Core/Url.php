<?php

namespace App\Core;

class Url
{
    public static function to(string $path = '/'): string
    {
        $base = rtrim((string) Config::get('app.base_path', ''), '/');
        $path = '/' . ltrim($path, '/');
        if ($path === '/') {
            return $base === '' ? '/' : $base . '/';
        }
        return $base . $path;
    }

    public static function asset(string $path): string
    {
        return self::to('/assets/' . ltrim($path, '/'));
    }

    public static function current(): string
    {
        return Request::path();
    }
}
