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

// Ownership check in SQL: Students only edit their own request; Admins edit any
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
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 border-bottom">
            <h1 class="h4 mb-0">Edit Tutoring Request #<?= (int)$id ?></h1>
        </div>
        <div class="card-body p-4">
            <?php if ($r['status'] !== 'Open'): ?>
                <div class="alert alert-warning" role="alert">
                    Only Open requests may be edited. This request has status <strong><?= escapeHtml($r['status']) ?></strong>.
                </div>
                <a class="btn btn-secondary" href="request-details.php?request_id=<?= (int)$id ?>">Back to Details</a>
            <?php else: ?>
                <form method="post" action="update-request.php" novalidate>
                    <input type="hidden" name="request_id" value="<?= (int)$id ?>">
                    <input type="hidden" name="csrf_token" value="<?= escapeHtml(csrfToken()) ?>">

                    <div class="mb-3">
                        <label class="form-label" for="course">Course</label>
                        <select class="form-select" id="course" name="course" required>
                            <?php foreach (['IS 6465', 'IS 6495', 'IS 6570'] as $c): ?>
                                <option value="<?= escapeHtml($c) ?>" <?= $c === $r['course'] ? 'selected' : '' ?>><?= escapeHtml($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="reason">What would you like help with?</label>
                        <textarea class="form-control" id="reason" name="reason" rows="4" minlength="10" maxlength="500" required><?= escapeHtml($topic['reason']) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="preferreddate">Preferred Date</label>
                        <input class="form-control" id="preferreddate" name="preferreddate" type="date" value="<?= escapeHtml($r['preferred_date']) ?>" min="<?= date('Y-m-d') ?>" required>
                    </div>

                    <fieldset class="mb-3">
                        <legend class="col-form-label pt-0 fw-semibold">Topics</legend>
                        <?php foreach (['Exam review', 'Practice problems', 'Study plan'] as $t): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="topics[]" id="t_<?= md5($t) ?>" value="<?= escapeHtml($t) ?>" <?= in_array($t, $topic['topics'], true) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="t_<?= md5($t) ?>"><?= escapeHtml($t) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </fieldset>

                    <fieldset class="mb-4">
                        <legend class="col-form-label pt-0 fw-semibold">Preferred Time</legend>
                        <?php foreach (['Morning', 'Afternoon', 'Evening'] as $t): ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="preferredtime" id="time_<?= strtolower($t) ?>" value="<?= $t ?>" <?= $t === $r['preferred_time'] ? 'checked' : '' ?> required>
                                <label class="form-check-label" for="time_<?= strtolower($t) ?>"><?= $t ?></label>
                            </div>
                        <?php endforeach; ?>
                    </fieldset>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <a class="btn btn-outline-secondary" href="request-details.php?request_id=<?= (int)$id ?>">Cancel</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
