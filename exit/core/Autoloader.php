<?php
// core/Autoloader.php - Simple PSR-like autoloader without Composer
function autoload($class) {
    // Remove namespace if any (but we're avoiding namespaces)
    $file = str_replace('\\', '/', $class) . '.php';
    $possiblePaths = [
        __DIR__ . '/../app/Controllers/' . $file,
        __DIR__ . '/../app/Middleware/' . $file,
        __DIR__ . '/../database/seeders/' . $file,
        __DIR__ . '/../core/' . $file,
    ];
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
}