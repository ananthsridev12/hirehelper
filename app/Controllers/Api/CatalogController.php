<?php

namespace App\Controllers\Api;

use App\Core\Response;
use App\Models\Category;
use App\Models\Service;

class CatalogController extends BaseApiController
{
    public function categories(): void
    {
        Response::json(['categories' => (new Category())->active()]);
    }

    public function services(string $categorySlug): void
    {
        $category = (new Category())->findBySlug($categorySlug);
        if (!$category || !$category['is_active']) {
            $this->fail('Category not found.', 404);
            return;
        }
        $services = (new Service())->activeByCategory((int) $category['id']);
        Response::json(['category' => $category, 'services' => $services]);
    }

    public function serviceDetail(string $slug): void
    {
        $service = (new Service())->withCategoryBySlug($slug);
        if (!$service) {
            $this->fail('Service not found.', 404);
            return;
        }
        $rating = (new Service())->ratingSummary((int) $service['id']);
        Response::json(['service' => $service, 'rating' => $rating]);
    }
}
