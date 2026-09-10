<?php

namespace App\Controllers;

use App\Core\Response;
use App\Models\Service;

class ServiceController extends BaseController
{
    public function show(string $slug): void
    {
        $service = (new Service())->withCategoryBySlug($slug);
        if (!$service) {
            Response::notFound();
            return;
        }

        $rating = (new Service())->ratingSummary((int) $service['id']);
        view('services/show', ['service' => $service, 'rating' => $rating]);
    }
}
