<?php

namespace App\Core;

class Config
{
    private static ?array $data = null;

    public static function load(): void
    {
        if (self::$data === null) {
            $path = APP_ROOT . '/config/config.php';
            if (!is_file($path)) {
                die('Missing config/config.php. Copy config/config.sample.php to config/config.php and fill in your database credentials.');
            }
            self::$data = require $path;
        }
    }

    public static function get(string $key, $default = null)
    {
        self::load();
        $segments = explode('.', $key);
        $value = self::$data;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }
}
