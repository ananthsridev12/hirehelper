<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection === null) {
            $host = Config::get('db.host', 'localhost');
            $name = Config::get('db.name');
            $charset = Config::get('db.charset', 'utf8mb4');
            $dsn = "mysql:host={$host};dbname={$name};charset={$charset}";

            try {
                self::$connection = new PDO($dsn, Config::get('db.user'), Config::get('db.pass'), [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                if (Config::get('app.env') === 'local') {
                    die('Database connection failed: ' . $e->getMessage());
                }
                die('Database connection failed.');
            }
        }
        return self::$connection;
    }
}
