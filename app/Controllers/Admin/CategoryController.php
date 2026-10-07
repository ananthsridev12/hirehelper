<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Auth;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Str;
use App\Core\Upload;
use App\Models\Category;

class CategoryController extends BaseController
{
    public function index(): void
    {
        $this->requireRole(Auth::ROLE_ADMIN);
        $categories = (new Category())->all('sort_order ASC, name ASC');
        view('admin/categories', ['categories' => $categories, 'errors' => []], 'admin');
    }

    public function store(): void
    {
        $this->requireRole(Auth::ROLE_ADMIN);
        $this->verifyCsrf();

        $name = (string) Request::input('name', '');
        $icon = (string) Request::input('icon', 'home');
        $description = (string) Request::input('description', '');
        $sortOrder = (int) Request::input('sort_order', 0);

        $errors = [];
        if ($name === '') {
            $errors[] = 'Category name is required.';
        }

        try {
            $imagePath = Upload::image(Request::file('image'), 'categories');
        } catch (\RuntimeException $e) {
            $errors[] = $e->getMessage();
            $imagePath = null;
        }

        if (!empty($errors)) {
            $categories = (new Category())->all('sort_order ASC, name ASC');
            view('admin/categories', ['categories' => $categories, 'errors' => $errors], 'admin');
            return;
        }

        $categoryModel = new Category();
        $slug = Str::slug($name);
        if ($categoryModel->findBySlug($slug)) {
            $slug .= '-' . substr(md5(uniqid()), 0, 5);
        }

        $categoryModel->create([
            'name' => $name,
            'slug' => $slug,
            'icon' => $icon,
            'image_path' => $imagePath,
            'description' => $description,
            'sort_order' => $sortOrder,
            'is_active' => 1,
        ]);

        Flash::success('Category created.');
        redirect('/admin/categories');
    }

    public function toggle(string $id): void
    {
        $this->requireRole(Auth::ROLE_ADMIN);
        $this->verifyCsrf();

        $categoryModel = new Category();
        $category = $categoryModel->find((int) $id);
        if ($category) {
            $categoryModel->update((int) $id, ['is_active' => $category['is_active'] ? 0 : 1]);
            Flash::success('Category updated.');
        }
        redirect('/admin/categories');
    }

    public function destroy(string $id): void
    {
        $this->requireRole(Auth::ROLE_ADMIN);
        $this->verifyCsrf();

        try {
            (new Category())->delete((int) $id);
            Flash::success('Category deleted.');
        } catch (\PDOException $e) {
            Flash::error('This category still has services under it and cannot be deleted. Deactivate it instead.');
        }
        redirect('/admin/categories');
    }
}
