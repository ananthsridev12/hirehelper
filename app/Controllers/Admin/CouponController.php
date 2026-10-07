<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Auth;
use App\Core\Flash;
use App\Core\Request;
use App\Models\Coupon;

class CouponController extends BaseController
{
    public function index(): void
    {
        $this->requireRole(Auth::ROLE_ADMIN);
        $coupons = (new Coupon())->all('created_at DESC');
        view('admin/coupons', ['coupons' => $coupons, 'errors' => []], 'admin');
    }

    public function store(): void
    {
        $this->requireRole(Auth::ROLE_ADMIN);
        $this->verifyCsrf();

        $code = strtoupper(trim((string) Request::input('code', '')));
        $type = Request::input('discount_type', 'flat') === 'percent' ? 'percent' : 'flat';
        $value = (float) Request::input('discount_value', 0);
        $maxDiscount = Request::input('max_discount', '');
        $minAmount = (float) Request::input('min_booking_amount', 0);
        $usageLimit = Request::input('usage_limit', '');
        $expiresAt = Request::input('expires_at', '');

        $errors = [];
        if ($code === '' || !preg_match('/^[A-Z0-9]{3,30}$/', $code)) {
            $errors[] = 'Code must be 3-30 letters/numbers.';
        }
        if ($value <= 0) {
            $errors[] = 'Discount value must be greater than zero.';
        }

        if (!empty($errors)) {
            $coupons = (new Coupon())->all('created_at DESC');
            view('admin/coupons', ['coupons' => $coupons, 'errors' => $errors], 'admin');
            return;
        }

        try {
            (new Coupon())->create([
                'code' => $code,
                'discount_type' => $type,
                'discount_value' => $value,
                'max_discount' => $maxDiscount !== '' ? (float) $maxDiscount : null,
                'min_booking_amount' => $minAmount,
                'usage_limit' => $usageLimit !== '' ? (int) $usageLimit : null,
                'expires_at' => $expiresAt !== '' ? $expiresAt : null,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            Flash::success('Coupon created.');
        } catch (\PDOException $e) {
            Flash::error('A coupon with that code already exists.');
        }
        redirect('/admin/coupons');
    }

    public function toggle(string $id): void
    {
        $this->requireRole(Auth::ROLE_ADMIN);
        $this->verifyCsrf();

        $couponModel = new Coupon();
        $coupon = $couponModel->find((int) $id);
        if ($coupon) {
            $couponModel->update((int) $id, ['is_active' => $coupon['is_active'] ? 0 : 1]);
            Flash::success('Coupon updated.');
        }
        redirect('/admin/coupons');
    }
}
