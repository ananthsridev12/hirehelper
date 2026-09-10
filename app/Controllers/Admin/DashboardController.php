<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Auth;
use App\Core\Database;

class DashboardController extends BaseController
{
    public function index(): void
    {
        $this->requireRole(Auth::ROLE_ADMIN);

        $db = Database::connection();
        $stats = [
            'customers' => (int) $db->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn(),
            'providers' => (int) $db->query("SELECT COUNT(*) FROM users WHERE role = 'provider'")->fetchColumn(),
            'bookings_total' => (int) $db->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
            'bookings_pending' => (int) $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn(),
            'revenue_completed' => (float) $db->query("SELECT COALESCE(SUM(price), 0) FROM bookings WHERE status = 'completed'")->fetchColumn(),
        ];

        view('admin/dashboard', ['stats' => $stats], 'admin');
    }
}
