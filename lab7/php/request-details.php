<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../services/requestService.php';

$pdo = openDatabase();
$user = requireLogin($pdo);
requireRole($user, ['Student', 'Administrator']);

$id = filter_input(INPUT_GET, 'request_id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(400);
    exit('Invalid request ID');
}

// Ownership check in SQL: Students only see their own record; Admins see any
if ($user['role'] === 'Student') {
    $r = findRequestForStudent($pdo, $id, (int)$user['user_id']);
} else {
    $r = findRequest($pdo, $id);
}

if (!$r) {
    http_response_code(404);
    exit('Request not found');
}

$topic = decodeTopic($r['topic']);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/nav.php';
?>

<main class="container py-4" style="max-width: 720px;">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0">Tutoring Request #<?= (int)$id ?></h1>
            <?php
            $statusBadge = getStatusBadgeClass($r['status']);
            ?>
            <span class="badge <?= $statusBadge ?> fs-6"><?= escapeHtml($r['status']) ?></span>
        </div>
        <div class="card-body p-4">
            <dl class="row mb-4">
                <dt class="col-sm-4 text-muted">Student Name</dt>
                <dd class="col-sm-8 fw-semibold"><?= escapeHtml($r['student_name']) ?></dd>

                <dt class="col-sm-4 text-muted">Student Email</dt>
                <dd class="col-sm-8"><?= escapeHtml($r['student_email']) ?></dd>

                <dt class="col-sm-4 text-muted">Course</dt>
                <dd class="col-sm-8"><span class="badge bg-light text-dark border"><?= escapeHtml($r['course']) ?></span></dd>

                <dt class="col-sm-4 text-muted">What student needs help with</dt>
                <dd class="col-sm-8"><?= nl2br(escapeHtml($topic['reason'])) ?></dd>

                <dt class="col-sm-4 text-muted">Selected Topics</dt>
                <dd class="col-sm-8">
                    <?php if (!empty($topic['topics'])): ?>
                        <?php foreach ($topic['topics'] as $t): ?>
                            <span class="badge bg-secondary me-1"><?= escapeHtml($t) ?></span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="text-muted">None specified</span>
                    <?php endif; ?>
                </dd>

                <dt class="col-sm-4 text-muted">Preferred Date</dt>
                <dd class="col-sm-8"><?= escapeHtml($r['preferred_date']) ?></dd>

                <dt class="col-sm-4 text-muted">Preferred Time</dt>
                <dd class="col-sm-8"><?= escapeHtml($r['preferred_time']) ?></dd>

                <dt class="col-sm-4 text-muted">Date Submitted</dt>
                <dd class="col-sm-8 text-muted small"><?= escapeHtml($r['created_at']) ?></dd>
            </dl>

            <div class="d-flex flex-wrap gap-2 pt-2 border-top">
                <?php if ($r['status'] === 'Open'): ?>
                    <a class="btn btn-primary" href="edit-request.php?request_id=<?= (int)$id ?>">Edit Request</a>
                    <a class="btn btn-outline-danger" href="delete-request.php?request_id=<?= (int)$id ?>">Cancel Request</a>
                <?php endif; ?>
                <a href="requests.php" class="btn btn-secondary">Back to Requests</a>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
