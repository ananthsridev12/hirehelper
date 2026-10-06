<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Models\Address;

class AddressController extends BaseApiController
{
    public function index(): void
    {
        $user = $this->authenticate();
        Response::json(['addresses' => (new Address())->forUser((int) $user['id'])]);
    }

    public function store(): void
    {
        $user = $this->authenticate();

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

        if ($data['line1'] === '') $this->fail('Address line is required.');
        if ($data['city'] === '') $this->fail('City is required.');
        if (!preg_match('/^[0-9]{4,10}$/', $data['pincode'])) $this->fail('A valid pincode is required.');
        if (!preg_match('/^[0-9+\-\s]{7,15}$/', $data['phone'])) $this->fail('A valid contact phone is required.');

        $addressModel = new Address();
        $makeDefault = Request::input('is_default') !== null || empty($addressModel->forUser((int) $user['id']));
        if ($makeDefault) {
            $addressModel->clearDefault((int) $user['id']);
        }

        $data['user_id'] = $user['id'];
        $data['is_default'] = $makeDefault ? 1 : 0;
        $addressId = $addressModel->create($data);

        Response::json(['address' => $addressModel->find($addressId)], 201);
    }

    public function destroy(string $id): void
    {
        $user = $this->authenticate();
        $addressModel = new Address();
        $address = $addressModel->belongsToUser((int) $id, (int) $user['id']);
        if (!$address) {
            $this->fail('Address not found.', 404);
            return;
        }
        $addressModel->delete((int) $id);
        Response::json(['ok' => true]);
    }
}
