<?php
require_once __DIR__ . '/includes/auth.php';
startSecureSession();

if (!empty($_SESSION['user_id'])) {
    if (($_SESSION['role'] ?? '') === 'Student') {
        header('Location: php/requests.php', true, 302);
    } elseif (($_SESSION['role'] ?? '') === 'Administrator') {
        header('Location: php/requests.php', true, 302);
    } else {
        header('Location: php/profile.php', true, 302);
    }
    exit;
}

header('Location: php/login.php', true, 302);
exit;
