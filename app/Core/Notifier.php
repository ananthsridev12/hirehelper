<?php

namespace App\Core;

/**
 * Single call site for "tell this user something happened." Always writes
 * to the in-app notifications feed (read by both the website and the
 * Flutter app). OS-level push is a no-op until a Firebase project exists --
 * send() is the one place that would call the FCM HTTP v1 API once
 * config('push.fcm_project_id') etc. are filled in; until then it just
 * returns without doing anything, so every call site below is already
 * correct and needs no changes later.
 */
class Notifier
{
    public static function notify(int $userId, string $title, string $body, ?int $bookingId = null): void
    {
        $stmt = Database::connection()->prepare(
            "INSERT INTO notifications (user_id, title, body, booking_id, created_at) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$userId, $title, $body, $bookingId, date('Y-m-d H:i:s')]);

        self::sendPush($userId, $title, $body);
    }

    private static function sendPush(int $userId, string $title, string $body): void
    {
        if (!Config::get('push.fcm_project_id')) {
            // No Firebase project configured yet -- in-app notification
            // above is still recorded, OS push is simply skipped.
            return;
        }

        // TODO once a Firebase project exists: look up this user's rows in
        // device_tokens, mint a short-lived OAuth2 token from the service
        // account JSON named by config('push.fcm_service_account_path'),
        // and POST to
        // https://fcm.googleapis.com/v1/projects/{project_id}/messages:send
        // for each token. Deliberately not implemented against fake
        // credentials -- see README "Push notifications" section.
    }
}
