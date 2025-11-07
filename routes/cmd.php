<?php 
Router::get('/fix_permissions', function() {
    if ($_GET['key'] !== 'dino123') {
        http_response_code(403);
        echo "🚫 Access denied.";
        return;
    }

    $script = __DIR__ . '/../vendor/cmd/fix_permissions.php';
    require_once $script;
});
