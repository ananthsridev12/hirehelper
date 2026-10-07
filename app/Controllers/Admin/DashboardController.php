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
            'revenue_completed' => (float) $db->query("SELECT COALESCE(SUM(price - discount_amount), 0) FROM bookings WHERE status = 'completed'")->fetchColumn(),
            'open_tickets' => (int) $db->query("SELECT COUNT(*) FROM support_tickets WHERE status = 'open'")->fetchColumn(),
        ];

        view('admin/dashboard', ['stats' => $stats, 'trend' => $this->last7DaysTrend($db)], 'admin');
    }

    /**
     * Bookings created per day for the last 7 days, oldest first, for the
     * plain inline-SVG bar chart on the dashboard -- no charting library.
     */
    private function last7DaysTrend($db): array
    {
        $stmt = $db->prepare(
            "SELECT DATE(created_at) AS day, COUNT(*) AS total
             FROM bookings
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
             GROUP BY DATE(created_at)"
        );
        $stmt->execute();
        $byDay = [];
        foreach ($stmt->fetchAll() as $row) {
            $byDay[$row['day']] = (int) $row['total'];
        }

        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $trend[] = ['date' => $date, 'label' => date('D', strtotime($date)), 'count' => $byDay[$date] ?? 0];
        }
        return $trend;
    }
}
