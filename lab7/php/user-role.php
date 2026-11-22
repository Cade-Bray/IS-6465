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
    $newRole = trim((string)($_POST['role'] ?? ''));

    try {
        changeUserRole($pdo, $targetId, $newRole, (int)$currentUser['user_id']);
        header('Location: users.php?role_updated=1', true, 303);
        exit;
    } catch (DomainException | InvalidArgumentException $e) {
        $error = $e->getMessage();
    } catch (Throwable $e) {
        error_log($e->getMessage());
        $error = 'Unable to update role. Please try again.';
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/nav.php';
?>

<main class="container py-4" style="max-width: 540px;">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 border-bottom">
            <h1 class="h4 mb-0">Update User Role</h1>
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

                <dt class="col-sm-4 text-muted">Current Role</dt>
                <dd class="col-sm-8"><span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle"><?= escapeHtml($targetUser['role']) ?></span></dd>
            </dl>

            <form method="post" action="user-role.php">
                <input type="hidden" name="csrf_token" value="<?= escapeHtml(csrfToken()) ?>">
                <input type="hidden" name="user_id" value="<?= (int)$targetId ?>">

                <div class="mb-4">
                    <label for="role" class="form-label fw-semibold">Select New Role</label>
                    <select class="form-select" id="role" name="role" style="min-width: 220px;" required>
                        <option value="Student" <?= $targetUser['role'] === 'Student' ? 'selected' : '' ?>>Student</option>
                        <option value="Tutor" <?= $targetUser['role'] === 'Tutor' ? 'selected' : '' ?>>Tutor</option>
                        <option value="Administrator" <?= $targetUser['role'] === 'Administrator' ? 'selected' : '' ?>>Administrator</option>
                    </select>
                    <div class="form-text">System rule: Demoting the last active administrator is prohibited.</div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary px-3">Save Role</button>
                    <a href="users.php" class="btn btn-sm btn-outline-secondary px-3">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

