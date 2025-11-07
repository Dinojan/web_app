<?php
// config/app.php
// Use getenv('KEY') for env vars
return [
    'name' => getenv('APP_NAME') ?: 'My MVC App',
    'env' => getenv('APP_ENV') ?: 'production',
    'debug' => (bool) (getenv('APP_DEBUG') ?: false),
    'url' => getenv('APP_URL') ?: 'http://localhost',
    'timezone' => 'UTC',
    'locale' => 'en',
];