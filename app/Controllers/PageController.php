<?php

namespace App\Controllers;

class PageController extends BaseController
{
    public function privacy(): void
    {
        view('pages/privacy');
    }

    public function terms(): void
    {
        view('pages/terms');
    }
}
