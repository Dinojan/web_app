<?php
// app/Middleware/GuestMiddleware.php
class GuestMiddleware {
    public function handle($router) {
        if (isset($_SESSION['user'])) {
            $router->redirect('/dashboard');
        }
    }
}