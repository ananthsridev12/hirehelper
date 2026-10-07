<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Auth;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Str;
use App\Core\Upload;
use App\Models\Category;
use App\Models\Service;

class ServiceController extends BaseController
{
    public function index(): void
    {
        $this->requireRole(Auth::ROLE_ADMIN);
        $services = $this->allWithCategoryName();
        $categories = (new Category())->active();
        view('admin/services', ['services' => $services, 'categories' => $categories, 'errors' => []], 'admin');
    }

    private function allWithCategoryName(): array
    {
        $db = \App\Core\Database::connection();
        $stmt = $db->query(
            "SELECT s.*, c.name AS category_name FROM services s
             INNER JOIN categories c ON c.id = s.category_id ORDER BY s.id DESC"
        );
        return $stmt->fetchAll();
    }

    public function store(): void
    {
        $this->requireRole(Auth::ROLE_ADMIN);
        $this->verifyCsrf();

        $name = (string) Request::input('name', '');
        $categoryId = (int) Request::input('category_id', 0);
        $price = (float) Request::input('price', 0);
        $duration = (int) Request::input('duration_minutes', 60);
        $description = (string) Request::input('description', '');

        $errors = [];
        if ($name === '') {
            $errors[] = 'Service name is required.';
        }
        if (!(new Category())->find($categoryId)) {
            $errors[] = 'Please choose a valid category.';
        }
        if ($price <= 0) {
            $errors[] = 'Price must be greater than zero.';
        }

        try {
            $imagePath = Upload::image(Request::file('image'), 'services');
        } catch (\RuntimeException $e) {
            $errors[] = $e->getMessage();
            $imagePath = null;
        }

        if (!empty($errors)) {
            $services = $this->allWithCategoryName();
            $categories = (new Category())->active();
            view('admin/services', ['services' => $services, 'categories' => $categories, 'errors' => $errors], 'admin');
            return;
        }

        $serviceModel = new Service();
        $slug = Str::slug($name);
        if ($serviceModel->findBySlug($slug)) {
            $slug .= '-' . substr(md5(uniqid()), 0, 5);
        }

        $serviceModel->create([
            'category_id' => $categoryId,
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'image_path' => $imagePath,
            'price' => $price,
            'duration_minutes' => $duration,
            'is_active' => 1,
        ]);

        Flash::success('Service created.');
        redirect('/admin/services');
    }

    public function toggle(string $id): void
    {
        $this->requireRole(Auth::ROLE_ADMIN);
        $this->verifyCsrf();

        $serviceModel = new Service();
        $service = $serviceModel->find((int) $id);
        if ($service) {
            $serviceModel->update((int) $id, ['is_active' => $service['is_active'] ? 0 : 1]);
            Flash::success('Service updated.');
        }
        redirect('/admin/services');
    }

    public function destroy(string $id): void
    {
        $this->requireRole(Auth::ROLE_ADMIN);
        $this->verifyCsrf();

        try {
            (new Service())->delete((int) $id);
            Flash::success('Service deleted.');
        } catch (\PDOException $e) {
            Flash::error('This service has bookings and cannot be deleted. Deactivate it instead.');
        }
        redirect('/admin/services');
    }
}
