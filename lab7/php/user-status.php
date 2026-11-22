<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../services/userService.php';
require_once __DIR__ . '/../services/requestService.php';

$pdo = openDatabase();
$currentUser = requireLogin($pdo);
requireRole($currentUser, ['Administrator']);

$targetId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

if (!$targetId || $targetId < 1) {
    http_response_code(400);
    exit('Invalid user ID.');
}

$targetUser = findUserById($pdo, $targetId);
if (!$targetUser) {
    http_response_code(404);
    exit('User not found.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST);
    $newStatus = trim((string)($_POST['status'] ?? ''));

    try {
        changeUserStatus($pdo, $targetId, $newStatus, (int)$currentUser['user_id']);
        header('Location: users.php?status_updated=1', true, 303);
        exit;
    } catch (DomainException | InvalidArgumentException $e) {
        $error = $e->getMessage();
    } catch (Throwable $e) {
        error_log($e->getMessage());
        $error = 'Unable to update status. Please try again.';
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/nav.php';
?>

<main class="container py-4" style="max-width: 540px;">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 border-bottom">
            <h1 class="h4 mb-0">Activate or Deactivate Account</h1>
        </div>
        <div class="card-body p-4">
            <?php if ($error !== ''): ?>
                <div class="alert alert-danger py-2" role="alert"><?= escapeHtml($error) ?></div>
            <?php endif; ?>

            <dl class="row mb-4">
                <dt class="col-sm-4 text-muted">User Name</dt>
                <dd class="col-sm-8 fw-semibold"><?= escapeHtml($targetUser['full_name']) ?></dd>

                <dt class="col-sm-4 text-muted">Email</dt>
                <dd class="col-sm-8"><?= escapeHtml($targetUser['email']) ?></dd>

                <dt class="col-sm-4 text-muted">Current Status</dt>
                <dd class="col-sm-8">
                    <span class="badge <?= $targetUser['status'] === 'Active' ? 'bg-success-subtle text-success-emphasis border border-success-subtle' : 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle' ?>">
                        <?= escapeHtml($targetUser['status']) ?>
                    </span>
                </dd>
            </dl>

            <form method="post" action="user-status.php">
                <input type="hidden" name="csrf_token" value="<?= escapeHtml(csrfToken()) ?>">
                <input type="hidden" name="user_id" value="<?= (int)$targetId ?>">

                <div class="mb-4">
                    <label for="status" class="form-label fw-semibold">Select Account Status</label>
                    <select class="form-select" id="status" name="status" style="min-width: 280px;" required>
                        <option value="Active" <?= $targetUser['status'] === 'Active' ? 'selected' : '' ?>>Active (Can log in)</option>
                        <option value="Inactive" <?= $targetUser['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive (Login blocked)</option>
                    </select>
                    <div class="form-text">Inactive accounts cannot authenticate. Deactivating the sole active administrator is prohibited.</div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary px-3">Confirm Status Change</button>
                    <a href="users.php" class="btn btn-sm btn-outline-secondary px-3">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

