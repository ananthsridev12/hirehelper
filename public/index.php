<?php

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

require APP_ROOT . '/app/Core/Autoload.php';
require APP_ROOT . '/app/Core/helpers.php';

use App\Core\Config;
use App\Core\Request;
use App\Core\Router;

session_start();

Config::load();

if (Config::get('app.env') === 'local') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

$router = new Router();
require APP_ROOT . '/routes.php';

$router->dispatch(Request::method(), Request::path());
