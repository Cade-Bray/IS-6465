<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../services/requestService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('POST required');
}

verifyCsrfToken($_POST);

$id = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(400);
    exit('Invalid request ID');
}

$pdo = openDatabase();
$user = requireLogin($pdo);
requireRole($user, ['Student', 'Administrator']);

try {
    $isAdmin = ($user['role'] === 'Administrator');
    $userId = (int)$user['user_id'];

    $ok = reviseRequest($pdo, $id, $_POST, $userId, $isAdmin);
    if (!$ok) {
        http_response_code(409);
        exit('Request not found or not Open');
    }
    header('Location: request-details.php?request_id=' . $id, true, 303);
    exit;
} catch (InvalidArgumentException $e) {
    http_response_code(422);
    exit(escapeHtml($e->getMessage()));
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Unable to update request');
}
