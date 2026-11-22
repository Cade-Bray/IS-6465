<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../services/requestService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('POST required');
}

if (($_POST['form_name'] ?? '') !== 'tutoring_request') {
    http_response_code(400);
    exit('Invalid form submission');
}

verifyCsrfToken($_POST);

$pdo = openDatabase();
$user = requireLogin($pdo);
requireRole($user, ['Student']);

// Force authoritative student identity from authenticated session
$_POST['student_name'] = $user['full_name'];
$_POST['student_email'] = $user['email'];

try {
    $id = submitRequest($pdo, $_POST, (int)$user['user_id']);
    header('Location: request-details.php?request_id=' . $id, true, 303);
    exit;
} catch (InvalidArgumentException $e) {
    http_response_code(422);
    require_once __DIR__ . '/../includes/header.php';
    require_once __DIR__ . '/../includes/nav.php';
    echo '<main class="container py-4" style="max-width: 600px;">';
    echo '<div class="alert alert-danger shadow-sm">';
    echo '<h4 class="alert-heading">Request could not be submitted</h4>';
    echo '<p>' . escapeHtml($e->getMessage()) . '</p>';
    echo '<hr><a href="request-form.php" class="btn btn-outline-danger">Return to Form</a>';
    echo '</div></main>';
    require_once __DIR__ . '/../includes/footer.php';
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Unable to save tutoring request.');
}
