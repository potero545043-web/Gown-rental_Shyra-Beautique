<?php

/**
 * Router for PHP's built-in web server (php -S).
 *
 * Serves existing static files directly (images, css, js, fonts) and forwards
 * everything else to the Laravel front controller. Written to be safe on
 * Windows, where getcwd() returns backslashes and a naive __DIR__.'/index.php'
 * concatenation produces mixed separators that break require_once.
 */

$publicPath = __DIR__;
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');

// Hand real files back to the built-in server so it streams them with the
// correct Content-Type instead of routing them through Laravel.
if ($uri !== '/' && is_file($publicPath . $uri)) {
    return false;
}

require_once $publicPath . DIRECTORY_SEPARATOR . 'index.php';
