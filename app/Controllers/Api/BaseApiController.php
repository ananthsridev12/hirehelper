<?php

namespace App\Controllers\Api;

use App\Core\ApiAuth;
use App\Core\Response;

abstract class BaseApiController
{
    /**
     * Bearer-token auth, not the session CSRF check the web controllers
     * use -- a native app's token isn't sent automatically by anything
     * else the way a browser cookie is, so CSRF doesn't apply here.
     */
    protected function authenticate(): array
    {
        $user = ApiAuth::userFromRequest();
        if (!$user) {
            Response::json(['error' => 'Unauthenticated. Send an Authorization: Bearer <token> header.'], 401);
            exit;
        }
        return $user;
    }

    protected function requireRole(array $user, string $role): void
    {
        if ($user['role'] !== $role) {
            Response::json(['error' => 'Forbidden for this account type.'], 403);
            exit;
        }
    }

    protected function fail(string $message, int $status = 422): void
    {
        Response::json(['error' => $message], $status);
        exit;
    }
}
