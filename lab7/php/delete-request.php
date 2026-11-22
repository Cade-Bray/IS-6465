<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../services/requestService.php';

$pdo = openDatabase();
$user = requireLogin($pdo);
requireRole($user, ['Student', 'Administrator']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST);

    $id = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
    if (!$id || $id < 1) {
        http_response_code(400);
        exit('Invalid request ID');
    }

    try {
        $isAdmin = ($user['role'] === 'Administrator');
        $userId = (int)$user['user_id'];

        $ok = cancelRequest($pdo, $id, $userId, $isAdmin);
        if (!$ok) {
            http_response_code(409);
            exit('Request not found or not Open');
        }
        header('Location: request-details.php?request_id=' . $id, true, 303);
        exit;
    } catch (Throwable $e) {
        error_log($e->getMessage());
        http_response_code(500);
        exit('Unable to cancel request');
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit('Method not allowed');
}

$id = filter_input(INPUT_GET, 'request_id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(400);
    exit('Invalid request ID');
}

if ($user['role'] === 'Student') {
    $r = findRequestForStudent($pdo, $id, (int)$user['user_id']);
} else {
    $r = findRequest($pdo, $id);
}

if (!$r) {
    http_response_code(404);
    exit('Request not found');
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/nav.php';
?>

<main class="container py-4" style="max-width: 600px;">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-danger text-white py-3 text-center">
            <h1 class="h4 mb-0">Cancel Tutoring Request</h1>
        </div>
        <div class="card-body p-4 text-center">
            <p class="lead mb-4">Are you sure you want to cancel your request?</p>

            <?php if ($r['status'] === 'Open'): ?>
                <form method="post" action="delete-request.php">
                    <input type="hidden" name="request_id" value="<?= (int)$id ?>">
                    <input type="hidden" name="csrf_token" value="<?= escapeHtml(csrfToken()) ?>">

                    <div class="d-flex justify-content-center gap-2">
                        <button type="submit" class="btn btn-danger">Yes, Cancel Request</button>
                        <a href="request-details.php?request_id=<?= (int)$id ?>" class="btn btn-outline-secondary">Keep Request</a>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-warning text-center" role="alert">
                    Only Open requests can be cancelled. This request currently has status <strong><?= escapeHtml($r['status']) ?></strong>.
                </div>
                <a href="request-details.php?request_id=<?= (int)$id ?>" class="btn btn-secondary">Back to Details</a>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
