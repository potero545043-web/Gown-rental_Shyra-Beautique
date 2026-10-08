<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Serverless runtime paths
|--------------------------------------------------------------------------
|
| Vercel's deployment filesystem is read-only, and Laravel writes compiled
| package/service manifests plus framework cache, sessions and views to
| bootstrap/cache and storage. Neither is writable at runtime, so booting
| fails (a missing package manifest leaves the "view" binding unregistered).
| Redirect every writable path into the function's /tmp directory.
|
*/

$runtimePath = sys_get_temp_dir() . '/gown-rental';
$cachePath = $runtimePath . '/cache';

foreach ([
    $runtimePath . '/app/private',
    $runtimePath . '/app/public',
    $runtimePath . '/framework/cache/data',
    $runtimePath . '/framework/sessions',
    $runtimePath . '/framework/views',
    $runtimePath . '/logs',
    $cachePath,
] as $directory) {
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException("Unable to create Laravel runtime directory: {$directory}");
    }
}

foreach ([
    'APP_PACKAGES_CACHE' => $cachePath . '/packages.php',
    'APP_SERVICES_CACHE' => $cachePath . '/services.php',
    'APP_CONFIG_CACHE' => $cachePath . '/config.php',
    'APP_ROUTES_CACHE' => $cachePath . '/routes.php',
    'APP_EVENTS_CACHE' => $cachePath . '/events.php',
] as $key => $value) {
    putenv($key . '=' . $value);
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->useStoragePath($runtimePath);
$app->handleRequest(Request::capture());
