<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Flash;
use App\Core\Request;
use App\Models\Address;

class AddressController extends BaseController
{
    public function index(): void
    {
        $this->requireLogin();
        $addresses = (new Address())->forUser(Auth::id());
        view('account/addresses', [
            'addresses' => $addresses,
            'errors' => [],
            'googleMapsKey' => Config::get('maps.google_maps_key', ''),
        ]);
    }

    public function store(): void
    {
        $this->requireLogin();
        $this->verifyCsrf();

        $data = [
            'label' => (string) Request::input('label', 'Home'),
            'line1' => (string) Request::input('line1', ''),
            'line2' => (string) Request::input('line2', ''),
            'city' => (string) Request::input('city', ''),
            'state' => (string) Request::input('state', ''),
            'pincode' => (string) Request::input('pincode', ''),
            'phone' => (string) Request::input('phone', ''),
        ];

        $lat = Request::input('lat', '');
        $lng = Request::input('lng', '');
        $data['lat'] = $lat !== '' && is_numeric($lat) ? round((float) $lat, 7) : null;
        $data['lng'] = $lng !== '' && is_numeric($lng) ? round((float) $lng, 7) : null;

        $errors = [];
        if ($data['line1'] === '') {
            $errors[] = 'Address line is required.';
        }
        if ($data['city'] === '') {
            $errors[] = 'City is required.';
        }
        if (!preg_match('/^[0-9]{4,10}$/', $data['pincode'])) {
            $errors[] = 'A valid pincode is required.';
        }
        if (!preg_match('/^[0-9+\-\s]{7,15}$/', $data['phone'])) {
            $errors[] = 'A valid contact phone is required.';
        }

        if (!empty($errors)) {
            $addresses = (new Address())->forUser(Auth::id());
            view('account/addresses', [
                'addresses' => $addresses,
                'errors' => $errors,
                'googleMapsKey' => Config::get('maps.google_maps_key', ''),
            ]);
            return;
        }

        $addressModel = new Address();
        $makeDefault = Request::input('is_default') !== null || empty($addressModel->forUser(Auth::id()));
        if ($makeDefault) {
            $addressModel->clearDefault(Auth::id());
        }

        $data['user_id'] = Auth::id();
        $data['is_default'] = $makeDefault ? 1 : 0;
        $addressModel->create($data);

        Flash::success('Address saved.');
        redirect('/account/addresses');
    }

    public function destroy(string $id): void
    {
        $this->requireLogin();
        $this->verifyCsrf();

        $addressModel = new Address();
        $address = $addressModel->belongsToUser((int) $id, Auth::id());
        if ($address) {
            $addressModel->delete((int) $id);
            Flash::success('Address removed.');
        }
        redirect('/account/addresses');
    }
}
