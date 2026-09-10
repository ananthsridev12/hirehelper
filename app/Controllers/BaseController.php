<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;

abstract class BaseController
{
    protected function verifyCsrf(): void
    {
        if (!Csrf::verify(Request::input('_csrf'))) {
            Flash::error('Your session expired. Please try again.');
            redirect('/');
        }
    }

    protected function requireGuest(): void
    {
        if (Auth::check()) {
            redirect('/');
        }
    }

    protected function requireLogin(): void
    {
        if (!Auth::check()) {
            Flash::error('Please log in to continue.');
            redirect('/login');
        }
    }

    protected function requireRole(string $role): void
    {
        $this->requireLogin();
        if (Auth::role() !== $role) {
            Response::notFound();
            exit;
        }
    }
}
