<?php
// app/Middleware/AuthMiddleware.php
class AuthMiddleware {
    public function handle($router) {
        if (!isset($_SESSION['user'])) {
            $router->redirect('/login');
        }
    }
}