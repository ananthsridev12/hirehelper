<?php

declare(strict_types=1);

// On hosting where the document root can't be pointed at <repo>/public and
// this directory (public/) IS the live web root, app/, database/ and
// config/ must live outside it -- see public/app-root.sample.php. Locally,
// and on hosting where the document root is <repo>/public, that override
// file won't exist and we fall back to the repo root.
$appRootOverride = __DIR__ . '/app-root.php';
define('APP_ROOT', is_file($appRootOverride) ? require $appRootOverride : dirname(__DIR__));

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
