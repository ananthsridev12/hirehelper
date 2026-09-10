<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Request;
use App\Models\Category;
use App\Models\ProviderProfile;
use App\Models\User;

class AuthController extends BaseController
{
    public function showRegister(): void
    {
        $this->requireGuest();
        $categories = (new Category())->active();
        view('auth/register', ['categories' => $categories, 'errors' => [], 'old' => []]);
    }

    public function register(): void
    {
        $this->requireGuest();
        $this->verifyCsrf();

        $old = [
            'name' => Request::input('name', ''),
            'email' => Request::input('email', ''),
            'phone' => Request::input('phone', ''),
            'role' => Request::input('role', 'customer'),
            'city' => Request::input('city', ''),
        ];
        $categoryIds = array_map('intval', (array) ($_POST['categories'] ?? []));

        $errors = [];
        $name = $old['name'];
        $email = strtolower($old['email']);
        $phone = $old['phone'];
        $password = (string) Request::input('password', '');
        $role = $old['role'] === 'provider' ? 'provider' : 'customer';
        $city = $old['city'];

        if ($name === '') {
            $errors[] = 'Name is required.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email is required.';
        }
        if ($phone === '' || !preg_match('/^[0-9+\-\s]{7,15}$/', $phone)) {
            $errors[] = 'A valid phone number is required.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($role === 'provider' && ($city === '' || empty($categoryIds))) {
            $errors[] = 'Providers must specify a city and at least one service category.';
        }

        $userModel = new User();
        if (empty($errors) && $userModel->findByEmail($email)) {
            $errors[] = 'An account with that email already exists.';
        }

        if (!empty($errors)) {
            $categories = (new Category())->active();
            view('auth/register', ['categories' => $categories, 'errors' => $errors, 'old' => $old]);
            return;
        }

        $userId = $userModel->create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if ($role === 'provider') {
            $profile = new ProviderProfile();
            $profile->createForUser($userId, [
                'bio' => '',
                'city' => $city,
                'is_verified' => 0,
                'is_available' => 1,
            ]);
            $profile->setCategories($userId, $categoryIds);
        }

        $user = $userModel->find($userId);
        Auth::login($user);
        Flash::success('Welcome, ' . $name . '!');
        redirect($role === 'provider' ? '/provider/dashboard' : '/');
    }

    public function showLogin(): void
    {
        $this->requireGuest();
        view('auth/login', ['errors' => [], 'old' => []]);
    }

    public function login(): void
    {
        $this->requireGuest();
        $this->verifyCsrf();

        $email = strtolower((string) Request::input('email', ''));
        $password = (string) Request::input('password', '');

        $userModel = new User();
        $user = $userModel->findByEmail($email);

        if (!$user || !$userModel->verifyPassword($user, $password)) {
            view('auth/login', ['errors' => ['Invalid email or password.'], 'old' => ['email' => $email]]);
            return;
        }

        if ($user['status'] !== 'active') {
            view('auth/login', ['errors' => ['Your account is suspended. Contact support.'], 'old' => ['email' => $email]]);
            return;
        }

        Auth::login($user);

        $destination = match ($user['role']) {
            'admin' => '/admin',
            'provider' => '/provider/dashboard',
            default => '/',
        };
        redirect($destination);
    }

    public function logout(): void
    {
        $this->verifyCsrf();
        Auth::logout();
        redirect('/');
    }
}
