<?php
session_start();

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool {
    return current_user() !== null;
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: /login.php');
        exit();
    }
}

function has_role(array $roles): bool {
    $user = current_user();
    if (!$user) {
        return false;
    }
    return in_array($user['role'], $roles, true);
}

function require_role(array $roles): void {
    if (!has_role($roles)) {
        header('Location: /dashboard.php');
        exit();
    }
}

function login_user(array $user): void {
    $_SESSION['user'] = $user;
}

function logout_user(): void {
    $_SESSION = [];
    session_destroy();
}

function status_label(string $status): string {
    switch ($status) {
        case 'PENDING':
            return 'To Do';
        case 'IN_PROGRESS':
            return 'In Progress';
        case 'COMPLETED':
            return 'Done';
        default:
            return $status;
    }
}

function status_badge_class(string $status): string {
    switch ($status) {
        case 'PENDING':
            return 'warning';
        case 'IN_PROGRESS':
            return 'primary';
        case 'COMPLETED':
            return 'success';
        default:
            return 'primary';
    }
}

function priority_badge_class(string $priority): string {
    switch ($priority) {
        case 'HIGH':
            return 'danger';
        case 'MEDIUM':
            return 'warning';
        case 'LOW':
        default:
            return 'success';
    }
}
