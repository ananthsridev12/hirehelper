<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Models\User;

class WalletController extends BaseController
{
    public function index(): void
    {
        $this->requireLogin();
        $user = Auth::user();
        $history = (new User())->walletHistory(Auth::id());
        view('account/wallet', ['user' => $user, 'history' => $history]);
    }
}
