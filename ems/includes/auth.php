<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// ── User Auth ────────────────────────────────────────────────────
function isUserLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function requireUser(): void {
    if (!isUserLoggedIn()) {
        header('Location: ' . BASE_URL . '/user_login.php');
        exit;
    }
}

function currentUser(): array {
    return [
        'id'    => $_SESSION['user_id']   ?? 0,
        'name'  => $_SESSION['user_name'] ?? '',
        'email' => $_SESSION['user_email']?? '',
    ];
}

// ── Admin Auth ───────────────────────────────────────────────────
function isAdminLoggedIn(): bool {
    return !empty($_SESSION['admin_id']);
}

function requireAdmin(): void {
    if (!isAdminLoggedIn()) {
        header('Location: ' . ADMIN_PATH . '/login.php');
        exit;
    }
}

function currentAdmin(): array {
    return [
        'id'   => $_SESSION['admin_id']   ?? 0,
        'name' => $_SESSION['admin_name'] ?? 'Admin',
    ];
}

// ── CSRF ─────────────────────────────────────────────────────────
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        die('Invalid request token.');
    }
}

// ── Flash messages ───────────────────────────────────────────────
function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash(): ?array {
    if (!isset($_SESSION['flash'])) return null;
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $f;
}
