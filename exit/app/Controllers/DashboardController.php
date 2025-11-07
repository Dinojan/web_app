<?php
// app/Controllers/DashboardController.php
class DashboardController {
    public function index() {
        return view('welcome.index', ['title' => 'Welcome']);
    }
    public function dashboard() {
       return view('admin.index', ['title' => 'Dashboard']);
    }
    public function profile($id) {
        $title = 'Profile';
        ob_start();
        ?>
        <h1>Profile for ID: <?php echo htmlspecialchars($id); ?></h1>
        <?php
        $content = ob_get_clean();
        include __DIR__ . '/../../resources/views/layouts/app.php';
    }
    public function adminDashboard() {
        $title = 'Admin Dashboard';
        ob_start();
        ?>
        <h1>Admin Dashboard</h1>
        <p>Admin area.</p>
        <?php
        $content = ob_get_clean();
        include __DIR__ . '/../../resources/views/layouts/app.php';
    }
}