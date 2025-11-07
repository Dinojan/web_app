<?php
// routes/web.php
// Basic routes (string format: Controller@method)
Router::get('/', 'DashboardController@index', 'home');
Router::get('/dashboard', 'DashboardController@dashboard', 'dashboard');
Router::get('/profile/{id}', 'DashboardController@profile', 'profile.show');
// Resource example (assumes PostController exists or create it)
Router::resource('posts', 'PostController');
// Group example with middleware
Router::group(['prefix' => 'admin', 'middleware' => ['auth']], function() {
    Router::get('/dashboard', 'DashboardController@adminDashboard');
});
// Closure example
Router::post('/login', function() {
    // Handle login
    echo 'Login processed';
}, 'login');