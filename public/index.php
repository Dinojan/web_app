<?php
// public/index.php

define('LARAVEL_START', microtime(true));
session_start();

// Load .env
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', trim($line), 2) + [1 => ''];
        putenv("$name=$value");
        $_ENV[$name] = $value;
    }
}
// Define BASE_URL
// define('BASE_URL', rtrim(getenv('APP_URL') ?: 'https://a2.dnipos.com/', '/'));
define('BASE_URL', rtrim(getenv('APP_URL') ?: 'http://localhost/web_app_1/', '/'));
define('SKIP_ROOT', (bool)getenv('IS_CPANEL') ?'': 'public/');
// Load router
require_once __DIR__ . '/../core/Autoloader.php';
require_once __DIR__ . '/../core/View.php'; 
require_once __DIR__ . '/../vendor/Router.php';
require_once __DIR__ . '/../core/helpers.php';


// Create router instance (Singleton)
$router = Router::getInstance();
$router->loadRoutes();
$router->route();
$response = $router->dispatch();

if (is_string($response)) {
    echo $response;
}
