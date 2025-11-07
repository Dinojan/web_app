<?php
// core/helpers.php
use Core\View;
if (!function_exists('route')) {
    function route($name, $parameters = []) {
        $router = Router::getInstance();
        return $router->url($name, $parameters);
    }
}


// Global view helper (add to helpers.php)
function view($view, $data = []) {
    // use Core\View;
   $viewPath = __DIR__ . "/../resources/views/" . str_replace('.', '/', $view) . ".php";
    $viewEngine = new View($data);
    return $viewEngine->render($viewPath, $data);
}

// view_path helper for includes
function view_path($view) {
    return __DIR__ . "/../resources/views/" . str_replace('.', '/', $view) . ".php";
}


// Fake auth() for demo (replace with your auth system)
function auth() {
    return (object) ['check' => function() { return isset($_SESSION['user']); }];
}
function csrf_token() { return bin2hex(random_bytes(32)); }