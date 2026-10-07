<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Request;
use App\Models\SupportTicket;

class SupportController extends BaseController
{
    public function create(): void
    {
        $this->requireLogin();
        view('support/create', ['errors' => []]);
    }

    public function store(): void
    {
        $this->requireLogin();
        $this->verifyCsrf();

        $subject = trim((string) Request::input('subject', ''));
        $message = trim((string) Request::input('message', ''));

        if ($subject === '' || $message === '') {
            view('support/create', ['errors' => ['Please fill in both the subject and message.']]);
            return;
        }

        (new SupportTicket())->create([
            'user_id' => Auth::id(),
            'booking_id' => null,
            'subject' => $subject,
            'message' => $message,
            'status' => 'open',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        Flash::success('Thanks for reaching out — we\'ll get back to you soon.');
        redirect('/');
    }
}
