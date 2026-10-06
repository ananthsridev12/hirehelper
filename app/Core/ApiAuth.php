<?php

namespace App\Core;

use App\Models\User;

/**
 * Bearer-token auth for the Flutter app. Entirely separate from Auth.php,
 * which is the web app's PHP-session login -- the two never interact.
 */
class ApiAuth
{
    public static function issue(int $userId, string $deviceLabel = ''): string
    {
        $plainToken = bin2hex(random_bytes(40));
        $stmt = Database::connection()->prepare(
            "INSERT INTO api_tokens (user_id, token_hash, device_label, created_at) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$userId, hash('sha256', $plainToken), $deviceLabel, date('Y-m-d H:i:s')]);
        return $plainToken;
    }

    public static function userFromRequest(): ?array
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return null;
        }
        return self::userFromToken($matches[1]);
    }

    public static function userFromToken(string $plainToken): ?array
    {
        $stmt = Database::connection()->prepare(
            "SELECT user_id FROM api_tokens WHERE token_hash = ? LIMIT 1"
        );
        $stmt->execute([hash('sha256', $plainToken)]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $update = Database::connection()->prepare("UPDATE api_tokens SET last_used_at = ? WHERE token_hash = ?");
        $update->execute([date('Y-m-d H:i:s'), hash('sha256', $plainToken)]);

        $user = (new User())->find((int) $row['user_id']);
        if (!$user || $user['status'] !== 'active') {
            return null;
        }
        return $user;
    }

    public static function revokeCurrent(): void
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return;
        }
        $stmt = Database::connection()->prepare("DELETE FROM api_tokens WHERE token_hash = ?");
        $stmt->execute([hash('sha256', $matches[1])]);
    }
}
