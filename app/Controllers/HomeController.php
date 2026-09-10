<?php

namespace App\Controllers;

use App\Models\Category;

class HomeController extends BaseController
{
    public function index(): void
    {
        $categories = (new Category())->active();
        view('home/index', ['categories' => $categories]);
    }
}
