<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Auth;
use App\Core\Flash;
use App\Models\SupportTicket;

class SupportController extends BaseController
{
    public function index(): void
    {
        $this->requireRole(Auth::ROLE_ADMIN);
        $tickets = (new SupportTicket())->allWithUser();
        view('admin/support', ['tickets' => $tickets], 'admin');
    }

    public function resolve(string $id): void
    {
        $this->requireRole(Auth::ROLE_ADMIN);
        $this->verifyCsrf();

        (new SupportTicket())->update((int) $id, ['status' => 'resolved']);
        Flash::success('Ticket marked resolved.');
        redirect('/admin/support');
    }
}
