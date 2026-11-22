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

<main class="container py-4 flex-grow-1" style="flex: 1 0 auto;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><?= escapeHtml($pageTitle) ?></h1>
            <p class="text-muted small mb-0">
                <?= $user['role'] === 'Student' ? 'Showing requests submitted under your student account' : 'Systemwide tutoring request administration' ?>
            </p>
        </div>
        <?php if ($user['role'] === 'Student'): ?>
            <a href="request-form.php" class="btn btn-primary">Request a Session</a>
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

    <div class="card shadow-sm border-0">
        <?php if (empty($rows)): ?>
            <div class="card-body p-5 text-center text-muted">
                <p class="lead mb-2">No matching requests found.</p>
                <?php if ($user['role'] === 'Student'): ?>
                    <p class="small mb-3">You do not have any tutoring requests matching your criteria.</p>
                    <a href="request-form.php" class="btn btn-sm btn-primary">Submit a New Request</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" style="width: 70px;">ID</th>
                            <?php if ($user['role'] === 'Administrator'): ?>
                                <th scope="col">Student</th>
                            <?php endif; ?>
                            <th scope="col">Course</th>
                            <th scope="col">Details</th>
                            <th scope="col">Date</th>
                            <th scope="col">Time</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-end">Action</th>
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
                                <td class="fw-semibold">#<?= (int)$r['request_id'] ?></td>
                                <?php if ($user['role'] === 'Administrator'): ?>
                                    <td>
                                        <div class="fw-semibold"><?= escapeHtml($r['student_name']) ?></div>
                                        <div class="small text-muted"><?= escapeHtml($r['student_email'] ?? '') ?></div>
                                    </td>
                                <?php endif; ?>
                                <td><span class="badge bg-light text-dark border"><?= escapeHtml($r['course']) ?></span></td>
                                <td class="text-truncate" style="max-width: 260px;" title="<?= escapeHtml($topic['reason']) ?>">
                                    <?= escapeHtml($topic['reason']) ?>
                                </td>
                                <td><?= escapeHtml($r['preferred_date']) ?></td>
                                <td><?= escapeHtml($r['preferred_time']) ?></td>
                                <td><span class="badge <?= $statusBadge ?>"><?= escapeHtml($r['status']) ?></span></td>
                                <td class="text-end">
                                    <a href="request-details.php?request_id=<?= (int)$r['request_id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
