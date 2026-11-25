<?php

/*
|--------------------------------------------------------------------------
| Vercel entry point
|--------------------------------------------------------------------------
| Vercel runs PHP as a serverless function with a read-only file system,
| except for /tmp. Point Laravel's writable caches there, log to stderr
| (visible in Vercel's logs), then hand over to the normal front controller.
*/

$serverless = [
    'APP_CONFIG_CACHE' => '/tmp/config.php',
    'APP_EVENTS_CACHE' => '/tmp/events.php',
    'APP_PACKAGES_CACHE' => '/tmp/packages.php',
    'APP_ROUTES_CACHE' => '/tmp/routes.php',
    'APP_SERVICES_CACHE' => '/tmp/services.php',
    'VIEW_COMPILED_PATH' => '/tmp/views',
    'LOG_CHANNEL' => 'stderr',
];

foreach ($serverless as $key => $value) {
    if (getenv($key) === false) {
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

if (! is_dir('/tmp/views')) {
    @mkdir('/tmp/views', 0755, true);
}

require __DIR__.'/../public/index.php';
