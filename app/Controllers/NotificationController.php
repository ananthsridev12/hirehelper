<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Models\Notification;

class NotificationController extends BaseController
{
    public function index(): void
    {
        $this->requireLogin();
        $notifications = (new Notification())->forUser(Auth::id());
        view('notifications/index', ['notifications' => $notifications]);
    }

    public function markRead(): void
    {
        $this->requireLogin();
        $this->verifyCsrf();
        (new Notification())->markAllRead(Auth::id());
        redirect('/notifications');
    }
}
