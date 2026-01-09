<?php
require_once __DIR__ . '/auth.php';

function nav_items(string $role): array {
    $base = [
        ['label' => 'Dashboard', 'path' => '/dashboard.php'],
        ['label' => 'Tasks', 'path' => '/tasks.php'],
        ['label' => 'Notifications', 'path' => '/notifications.php'],
        ['label' => 'Profile', 'path' => '/profile.php'],
        ['label' => 'Settings', 'path' => '/settings.php'],
    ];

    if (in_array($role, ['MANAGER', 'HR_ADMIN'], true)) {
        $base[] = ['label' => 'Reports', 'path' => '/reports.php'];
    }

    if ($role === 'HR_ADMIN') {
        $base[] = ['label' => 'User Management', 'path' => '/user_management.php'];
    }

    return $base;
}

function render_layout_start(string $title, bool $showNewTask = false): void {
    $user = current_user();
    $role = $user['role'] ?? 'EMPLOYEE';
    $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $items = nav_items($role);

    echo "<!DOCTYPE html>";
    echo "<html lang=\"en\">";
    echo "<head>";
    echo "<meta charset=\"UTF-8\">";
    echo "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">";
    echo "<title>Tasker - {$title}</title>";
    echo "<link rel=\"stylesheet\" href=\"/assets/css/style.css\">";
    echo "</head>";
    echo "<body>";
    echo "<div class=\"layout\">";
    echo "<aside class=\"sidebar\">";
    echo "<div class=\"brand\">Tasker</div>";
    echo "<nav class=\"nav-section\">";
    foreach ($items as $item) {
        $active = $currentPath === $item['path'] ? 'active' : '';
        echo "<a class=\"nav-link {$active}\" href=\"{$item['path']}\">{$item['label']}</a>";
    }
    echo "</nav>";
    if ($user) {
        $name = htmlspecialchars($user['fullName']);
        $roleLabel = htmlspecialchars(str_replace('_', ' ', $user['role']));
        echo "<div class=\"profile-card\">";
        echo "<strong>{$name}</strong><br><span>{$roleLabel}</span>";
        echo "<div class=\"footer-links\"><a href=\"/logout.php\">Logout</a></div>";
        echo "</div>";
    }
    echo "</aside>";
    echo "<main class=\"main\">";
    echo "<header class=\"header\">";
    echo "<h1>{$title}</h1>";
    echo "<div class=\"header-actions\">";
    echo "<div class=\"search\"><input type=\"text\" placeholder=\"Search tasks...\"></div>";
    if ($showNewTask) {
        echo "<a class=\"button primary\" href=\"/task_create.php\">New Task</a>";
    }
    echo "</div>";
    echo "</header>";
    echo "<section class=\"content\">";
}

function render_layout_end(): void {
    echo "</section>";
    echo "</main>";
    echo "</div>";
    echo "<script src=\"/assets/js/app.js\"></script>";
    echo "</body></html>";
}
