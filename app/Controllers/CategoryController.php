<?php

namespace App\Controllers;

use App\Core\Response;
use App\Models\Category;
use App\Models\Service;

class CategoryController extends BaseController
{
    public function show(string $slug): void
    {
        $category = (new Category())->findBySlug($slug);
        if (!$category || !$category['is_active']) {
            Response::notFound();
            return;
        }

        $services = (new Service())->activeByCategory((int) $category['id']);
        view('categories/show', ['category' => $category, 'services' => $services]);
    }
}
