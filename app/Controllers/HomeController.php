<?php

namespace App\Controllers;

use App\Core\Request;
use App\Models\Category;
use App\Models\Service;

class HomeController extends BaseController
{
    public function index(): void
    {
        $categories = (new Category())->active();
        view('home/index', ['categories' => $categories]);
    }

    public function search(): void
    {
        $query = trim((string) Request::query('q', ''));
        $results = $query !== '' ? (new Service())->search($query) : [];
        view('home/search', ['query' => $query, 'results' => $results]);
    }
}
