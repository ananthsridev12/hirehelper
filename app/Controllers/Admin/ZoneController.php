<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Auth;
use App\Core\Flash;
use App\Core\Request;
use App\Models\ServiceablePincode;

class ZoneController extends BaseController
{
    public function index(): void
    {
        $this->requireRole(Auth::ROLE_ADMIN);
        $zones = (new ServiceablePincode())->all('pincode ASC');
        view('admin/zones', ['zones' => $zones, 'errors' => []], 'admin');
    }

    public function store(): void
    {
        $this->requireRole(Auth::ROLE_ADMIN);
        $this->verifyCsrf();

        $pincode = trim((string) Request::input('pincode', ''));
        $city = trim((string) Request::input('city', ''));

        if (!preg_match('/^[0-9]{4,10}$/', $pincode)) {
            Flash::error('Please enter a valid pincode.');
            redirect('/admin/zones');
            return;
        }

        try {
            (new ServiceablePincode())->create([
                'pincode' => $pincode,
                'city' => $city,
                'is_active' => 1,
            ]);
            Flash::success('Pincode added.');
        } catch (\PDOException $e) {
            Flash::error('That pincode is already in the list.');
        }
        redirect('/admin/zones');
    }

    public function toggle(string $id): void
    {
        $this->requireRole(Auth::ROLE_ADMIN);
        $this->verifyCsrf();

        $model = new ServiceablePincode();
        $zone = $model->find((int) $id);
        if ($zone) {
            $model->update((int) $id, ['is_active' => $zone['is_active'] ? 0 : 1]);
        }
        redirect('/admin/zones');
    }

    public function destroy(string $id): void
    {
        $this->requireRole(Auth::ROLE_ADMIN);
        $this->verifyCsrf();

        (new ServiceablePincode())->delete((int) $id);
        Flash::success('Removed.');
        redirect('/admin/zones');
    }
}
