<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../services/requestService.php';

$pdo = openDatabase();
$user = requireLogin($pdo);

// Role check: Students and Admins permitted; Tutors denied request workflow in Lab 7
requireRole($user, ['Student', 'Administrator']);

$course = trim((string)($_GET['course'] ?? ''));
$status = (string)($_GET['status'] ?? '');

if ($course !== '' && !in_array($course, ['IS 6465', 'IS 6495', 'IS 6570'], true)) {
    http_response_code(400);
    exit('Invalid course filter');
}

if ($status !== '' && !in_array($status, ['Open', 'Cancelled', 'Assigned', 'Completed'], true)) {
    http_response_code(400);
    exit('Invalid status filter');
}

// Ownership check in SQL: Students only see their own requests; Admins see all
if ($user['role'] === 'Student') {
    $rows = listRequestsForStudent($pdo, (int)$user['user_id'], $course, $status);
    $pageTitle = 'My Tutoring Requests';
} else {
    $rows = listRequests($pdo, $course, $status);
    $pageTitle = 'All Tutoring Requests';
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/nav.php';
?>

<main class="container py-4 pb-5 flex-grow-1" style="flex: 1 0 auto;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><?= escapeHtml($pageTitle) ?></h1>
            <p class="text-muted small mb-0">
                <?= $user['role'] === 'Student' ? 'Showing requests submitted under your student account' : 'Systemwide tutoring request administration' ?>
            </p>
        </div>
        <?php if ($user['role'] === 'Student'): ?>
            <a href="request-form.php" class="btn btn-primary px-3 fw-semibold">Request a Session</a>
        <?php endif; ?>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-3">
            <form method="get" action="requests.php" class="row g-2 justify-content-center align-items-center mx-auto" style="max-width: 780px;">
                <div class="col-12 col-sm-auto flex-grow-1" style="min-width: 200px; max-width: 280px;">
                    <select class="form-select" name="course" aria-label="Filter by Course" style="min-width: 180px;">
                        <option value="">All Courses</option>
                        <?php foreach (['IS 6465', 'IS 6495', 'IS 6570'] as $c): ?>
                            <option value="<?= escapeHtml($c) ?>" <?= $c === $course ? 'selected' : '' ?>><?= escapeHtml($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-sm-auto flex-grow-1" style="min-width: 200px; max-width: 280px;">
                    <select class="form-select" name="status" aria-label="Filter by Status" style="min-width: 180px;">
                        <option value="">All Statuses</option>
                        <?php foreach (['Open', 'Cancelled', 'Assigned', 'Completed'] as $st): ?>
                            <option value="<?= escapeHtml($st) ?>" <?= $st === $status ? 'selected' : '' ?>><?= escapeHtml($st) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-sm-auto d-flex gap-2 justify-content-center">
                    <button type="submit" class="btn btn-primary px-4 fw-semibold">Filter</button>
                    <?php if ($course !== '' || $status !== ''): ?>
                        <a href="requests.php" class="btn btn-outline-secondary px-3">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-5 overflow-hidden">
        <?php if (empty($rows)): ?>
            <div class="card-body p-5 text-center text-muted">
                <div class="mb-3">
                    <span class="d-inline-block p-3 rounded-circle bg-light text-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="currentColor" class="bi bi-inbox" viewBox="0 0 16 16">
                            <path d="M4.98 4a.5.5 0 0 0-.39.188L1.54 8H6a.5.5 0 0 1 .5.5 1.5 1.5 0 1 0 3 0A.5.5 0 0 1 10 8h4.46l-3.05-3.812A.5.5 0 0 0 11.02 4zm9.954 5H10.45a2.5 2.5 0 0 1-4.9 0H1.066l.32 2.562a.5.5 0 0 0 .497.438h12.234a.5.5 0 0 0 .496-.438zM3.809 3.563A1.5 1.5 0 0 1 4.981 3h6.038a1.5 1.5 0 0 1 1.172.563l3.7 4.625a1 1 0 0 1 .109 1.036l-1 8A1 1 0 0 1 14 18H2a1 1 0 0 1-.992-.876l-1-8a1 1 0 0 1 .11-1.036z"/>
                        </svg>
                    </span>
                </div>
                <h5 class="fw-semibold text-dark">No matching requests found</h5>
                <?php if ($user['role'] === 'Student'): ?>
                    <p class="small text-muted mb-3">You do not have any tutoring requests matching your criteria.</p>
                    <a href="request-form.php" class="btn btn-primary px-4 fw-semibold">Submit a Request</a>
                <?php else: ?>
                    <p class="small text-muted mb-0">No tutoring requests match the selected filters.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0 w-100">
                    <thead class="table-light">
                        <tr class="text-secondary small fw-semibold">
                            <th scope="col" style="width: 80px;" class="ps-4">ID</th>
                            <?php if ($user['role'] === 'Administrator'): ?>
                                <th scope="col" style="min-width: 180px;">Student</th>
                            <?php endif; ?>
                            <th scope="col" style="width: 120px;">Course</th>
                            <th scope="col">Help Details</th>
                            <th scope="col" style="width: 140px;">Preferred Date</th>
                            <th scope="col" style="width: 120px;">Time</th>
                            <th scope="col" style="width: 130px;">Status</th>
                            <th scope="col" class="text-end pe-4" style="width: 100px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r):
                            $topic = decodeTopic($r['topic']);
                            $statusBadge = match ($r['status']) {
                                'Open' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
                                'Cancelled' => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
                                'Assigned' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
                                'Completed' => 'bg-primary-subtle text-primary-emphasis border border-primary-subtle',
                                default => 'bg-light text-dark border',
                            };
                        ?>
                            <tr>
                                <td class="ps-4">
                                    <span class="font-monospace fw-semibold text-secondary">#<?= (int)$r['request_id'] ?></span>
                                </td>
                                <?php if ($user['role'] === 'Administrator'): ?>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= escapeHtml($r['student_name']) ?></div>
                                        <div class="small text-muted"><?= escapeHtml($r['student_email'] ?? '') ?></div>
                                    </td>
                                <?php endif; ?>
                                <td>
                                    <span class="badge bg-light text-dark border px-2 py-1 fw-medium"><?= escapeHtml($r['course']) ?></span>
                                </td>
                                <td>
                                    <div class="text-truncate text-secondary" style="max-width: 320px;" title="<?= escapeHtml($topic['reason']) ?>">
                                        <?= escapeHtml($topic['reason']) ?>
                                    </div>
                                    <?php if (!empty($topic['topics'])): ?>
                                        <div class="mt-1">
                                            <?php foreach ((array)$topic['topics'] as $t): ?>
                                                <span class="badge bg-light text-secondary border me-1 small"><?= escapeHtml($t) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="fw-medium text-dark"><?= escapeHtml($r['preferred_date']) ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border"><?= escapeHtml($r['preferred_time']) ?></span>
                                </td>
                                <td>
                                    <span class="badge <?= $statusBadge ?> px-3 py-1"><?= escapeHtml($r['status']) ?></span>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="request-details.php?request_id=<?= (int)$r['request_id'] ?>" class="btn btn-sm btn-outline-primary px-3 rounded-pill fw-medium">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white border-top py-3 px-4 d-flex justify-content-between align-items-center text-muted small">
                <span>Showing <strong><?= count($rows) ?></strong> tutoring request<?= count($rows) === 1 ? '' : 's' ?></span>
                <span><?= $user['role'] === 'Student' ? 'Student Portal' : 'Administrator View' ?></span>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
