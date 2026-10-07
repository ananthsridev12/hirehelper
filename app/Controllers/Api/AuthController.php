<?php

namespace App\Controllers\Api;

use App\Core\ApiAuth;
use App\Core\Request;
use App\Core\Response;
use App\Models\ProviderProfile;
use App\Models\User;

class AuthController extends BaseApiController
{
    public function register(): void
    {
        $name = (string) Request::input('name', '');
        $email = strtolower((string) Request::input('email', ''));
        $phone = (string) Request::input('phone', '');
        $password = (string) Request::input('password', '');
        $role = Request::input('role', 'customer') === 'provider' ? 'provider' : 'customer';
        $city = (string) Request::input('city', '');
        $categoryIds = array_map('intval', (array) Request::input('categories', []));
        $deviceLabel = (string) Request::input('device_label', '');
        $referralCode = trim((string) Request::input('referral_code', ''));

        $errors = [];
        if ($name === '') $errors[] = 'Name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
        if ($phone === '' || !preg_match('/^[0-9+\-\s]{7,15}$/', $phone)) $errors[] = 'A valid phone number is required.';
        if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
        if ($role === 'provider' && ($city === '' || empty($categoryIds))) {
            $errors[] = 'Providers must specify a city and at least one service category.';
        }

        $userModel = new User();
        if (empty($errors) && $userModel->findByEmail($email)) {
            $errors[] = 'An account with that email already exists.';
        }

        $referrer = null;
        if (empty($errors) && $referralCode !== '') {
            $referrer = $userModel->findByReferralCode($referralCode);
            if (!$referrer) $errors[] = 'That referral code was not found.';
        }

        if (!empty($errors)) {
            $this->fail(implode(' ', $errors));
            return;
        }

        $userId = $userModel->create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'status' => 'active',
            'referral_code' => $userModel->generateReferralCode(),
            'referred_by' => $referrer ? $referrer['id'] : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if ($role === 'provider') {
            $profile = new ProviderProfile();
            $profile->createForUser($userId, ['bio' => '', 'city' => $city, 'is_verified' => 0, 'is_available' => 1]);
            $profile->setCategories($userId, $categoryIds);
        }

        if ($referrer) {
            $userModel->adjustWallet($userId, User::REFERRAL_BONUS, 'Referral signup bonus');
            $userModel->adjustWallet((int) $referrer['id'], User::REFERRAL_BONUS, 'Referred ' . $name);
        }

        $user = $userModel->find($userId);
        $token = ApiAuth::issue($userId, $deviceLabel);
        Response::json(['user' => $this->publicUser($user), 'token' => $token], 201);
    }

    public function wallet(): void
    {
        $user = $this->authenticate();
        $userModel = new User();
        Response::json([
            'balance' => (float) $user['wallet_balance'],
            'referral_code' => $user['referral_code'],
            'history' => $userModel->walletHistory((int) $user['id']),
        ]);
    }

    public function login(): void
    {
        $email = strtolower((string) Request::input('email', ''));
        $password = (string) Request::input('password', '');
        $deviceLabel = (string) Request::input('device_label', '');

        $userModel = new User();
        $user = $userModel->findByEmail($email);
        if (!$user || !$userModel->verifyPassword($user, $password)) {
            $this->fail('Invalid email or password.', 401);
            return;
        }
        if ($user['status'] !== 'active') {
            $this->fail('Your account is suspended.', 403);
            return;
        }

        $token = ApiAuth::issue((int) $user['id'], $deviceLabel);
        Response::json(['user' => $this->publicUser($user), 'token' => $token]);
    }

    public function logout(): void
    {
        $this->authenticate();
        ApiAuth::revokeCurrent();
        Response::json(['ok' => true]);
    }

    public function me(): void
    {
        $user = $this->authenticate();
        Response::json(['user' => $this->publicUser($user)]);
    }

    private function publicUser(array $user): array
    {
        return [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'role' => $user['role'],
        ];
    }
}
