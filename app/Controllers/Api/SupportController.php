<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Models\SupportTicket;

class SupportController extends BaseApiController
{
    public function store(): void
    {
        $user = $this->authenticate();

        $subject = trim((string) Request::input('subject', ''));
        $message = trim((string) Request::input('message', ''));
        if ($subject === '' || $message === '') {
            $this->fail('Please fill in both the subject and message.');
            return;
        }

        (new SupportTicket())->create([
            'user_id' => $user['id'],
            'booking_id' => null,
            'subject' => $subject,
            'message' => $message,
            'status' => 'open',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        Response::json(['ok' => true], 201);
    }
}
