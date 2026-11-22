<?php
/**
 * Authentication, Session Management, and CSRF Protection Helpers
 * Following IS 6465 Chapter 12 Authentication Reading Handout
 */

function startSecureSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        if (!headers_sent()) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'secure' => $https,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        } else {
            @session_start();
        }
    }
}

function requireLogin(PDO $pdo): array
{
    startSecureSession();

    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }

    // 20 minute inactivity timeout (1200 seconds)
    $lastActivity = isset($_SESSION['last_activity']) ? (int)$_SESSION['last_activity'] : 0;
    if (time() - $lastActivity > 1200) {
        logoutSession();
        header('Location: login.php?expired=1');
        exit;
    }

    require_once dirname(__DIR__) . '/repositories/userPDO.php';
    $user = findUserById($pdo, (int)$_SESSION['user_id']);

    if (!$user || $user['status'] !== 'Active') {
        logoutSession();
        header('Location: login.php?inactive=1');
        exit;
    }

    // Refresh session with authoritative database state
    $_SESSION['last_activity'] = time();
    $_SESSION['display_name'] = $user['full_name'];
    $_SESSION['role'] = $user['role'];

    return $user;
}

function requireRole(array $user, array $allowedRoles): void
{
    if (!in_array($user['role'], $allowedRoles, true)) {
        http_response_code(403);
        $role = htmlspecialchars($user['role'] ?? 'Unknown', ENT_QUOTES, 'UTF-8');
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8">';
        echo '<title>403 Forbidden - Peer Tutoring Hub</title>';
        echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">';
        echo '<link rel="stylesheet" href="../css/styles.css"></head><body class="p-5">';
        echo '<div class="container" style="max-width: 600px;"><div class="alert alert-danger shadow-sm">';
        echo '<h4 class="alert-heading">403 - Access Denied</h4>';
        echo '<p>You are authenticated as <strong>' . $role . '</strong>, which does not have permission to access this page or action.</p>';
        echo '<hr><div class="d-flex justify-content-between">';
        echo '<a href="profile.php" class="btn btn-outline-danger">My Profile</a>';
        echo '<a href="login.php" class="btn btn-secondary">Switch Account</a>';
        echo '</div></div></div></body></html>';
        exit;
    }
}

function csrfToken(): string
{
    startSecureSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(array $input): void
{
    startSecureSession();
    $token = $input['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)$token)) {
        http_response_code(403);
        exit('Invalid or missing CSRF token.');
    }
}

function logoutSession(): void
{
    startSecureSession();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
}

function getCurrentUser(PDO $pdo): ?array
{
    startSecureSession();
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    require_once dirname(__DIR__) . '/repositories/userPDO.php';
    $user = findUserById($pdo, (int)$_SESSION['user_id']);
    if (!$user || $user['status'] !== 'Active') {
        return null;
    }
    return $user;
}
