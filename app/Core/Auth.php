<?php

namespace App\Core;

use App\Models\User;

class Auth
{
    public const ROLE_CUSTOMER = 'customer';
    public const ROLE_PROVIDER = 'provider';
    public const ROLE_ADMIN = 'admin';

    private static ?array $user = null;
    private static bool $loaded = false;

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        self::$user = $user;
        self::$loaded = true;
    }

    public static function logout(): void
    {
        unset($_SESSION['user_id']);
        self::$user = null;
        self::$loaded = true;
        session_regenerate_id(true);
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function user(): ?array
    {
        if (self::$loaded) {
            return self::$user;
        }
        self::$loaded = true;
        if (empty($_SESSION['user_id'])) {
            self::$user = null;
            return null;
        }
        $user = (new User())->find((int) $_SESSION['user_id']);
        self::$user = $user ?: null;
        return self::$user;
    }

    public static function id(): ?int
    {
        $user = self::user();
        return $user ? (int) $user['id'] : null;
    }

    public static function role(): ?string
    {
        $user = self::user();
        return $user ? $user['role'] : null;
    }

    public static function isRole(string $role): bool
    {
        return self::role() === $role;
    }
}
