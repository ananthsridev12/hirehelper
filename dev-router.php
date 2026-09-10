<?php

// Dev-only router for `php -S`, which doesn't read .htaccess. Mimics
// public/.htaccess: serve real static files directly, route everything
// else through public/index.php with the path in $_GET['r'].

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = __DIR__ . '/public' . $uri;
if ($uri !== '/' && is_file($file)) {
    return false;
}
$_GET['r'] = ltrim($uri, '/');
require __DIR__ . '/public/index.php';
