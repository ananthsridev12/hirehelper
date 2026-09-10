<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Auth;
use App\Core\Flash;
use App\Models\ProviderProfile;
use App\Models\User;

class ProviderController extends BaseController
{
    public function index(): void
    {
        $this->requireRole(Auth::ROLE_ADMIN);
        $providers = (new ProviderProfile())->allWithUser();
        view('admin/providers', ['providers' => $providers], 'admin');
    }

    public function verify(string $id): void
    {
        $this->requireRole(Auth::ROLE_ADMIN);
        $this->verifyCsrf();

        $profileModel = new ProviderProfile();
        $profile = $profileModel->findByUserId((int) $id);
        if ($profile) {
            $profileModel->updateForUser((int) $id, ['is_verified' => $profile['is_verified'] ? 0 : 1]);
            Flash::success('Provider verification updated.');
        }
        redirect('/admin/providers');
    }

    public function suspend(string $id): void
    {
        $this->requireRole(Auth::ROLE_ADMIN);
        $this->verifyCsrf();

        $userModel = new User();
        $user = $userModel->find((int) $id);
        if ($user && $user['role'] === 'provider') {
            $userModel->update((int) $id, ['status' => $user['status'] === 'active' ? 'suspended' : 'active']);
            Flash::success('Provider status updated.');
        }
        redirect('/admin/providers');
    }
}
