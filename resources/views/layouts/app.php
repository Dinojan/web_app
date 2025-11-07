<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title ?? 'My App'); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        header { background: #f4f4f4; padding: 10px; margin-bottom: 20px; }
        main { max-width: 800px; }
    </style>
</head>
<body>
    <header>
        <h2>My MVC App</h2>
        <nav><a href="<?php echo BASE_URL; ?>">Home</a> | <a href="<?php echo BASE_URL; ?>dashboard">Dashboard</a></nav>
    </header>
    <main>
        <?php echo $content; ?>
    </main>
    <footer style="margin-top: 40px; text-align: center; color: #666;">
        &copy; <?php echo date('Y'); ?> My MVC App
    </footer>
</body>
</html>