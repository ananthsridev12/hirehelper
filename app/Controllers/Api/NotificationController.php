<?php

namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\Notification;

class NotificationController extends BaseApiController
{
    public function index(): void
    {
        $user = $this->authenticate();
        $model = new Notification();
        Response::json([
            'notifications' => $model->forUser((int) $user['id']),
            'unread_count' => $model->unreadCount((int) $user['id']),
        ]);
    }

    public function markRead(): void
    {
        $user = $this->authenticate();
        (new Notification())->markAllRead((int) $user['id']);
        Response::json(['ok' => true]);
    }

    public function registerDeviceToken(): void
    {
        $user = $this->authenticate();
        $token = (string) Request::input('push_token', '');
        $platform = Request::input('platform', 'android') === 'ios' ? 'ios' : 'android';
        if ($token === '') {
            $this->fail('push_token is required.');
            return;
        }

        $db = Database::connection();
        $stmt = $db->prepare(
            "INSERT INTO device_tokens (user_id, push_token, platform, created_at) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), platform = VALUES(platform)"
        );
        $stmt->execute([$user['id'], $token, $platform, date('Y-m-d H:i:s')]);
        Response::json(['ok' => true]);
    }
}
