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

$hasRequests = hasTutoringRequests($pdo, $targetId);
$requestCount = countRequestsForStudentUser($pdo, $targetId);
$isSelf = ($targetId === (int)$currentUser['user_id']);
$isLastAdmin = ($targetUser['role'] === 'Administrator' && $targetUser['status'] === 'Active' && countActiveAdmins($pdo) <= 1);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST);

    try {
        deleteUserAccount($pdo, $targetId, (int)$currentUser['user_id']);
        header('Location: users.php?deleted=1', true, 303);
        exit;
    } catch (DomainException $e) {
        $error = $e->getMessage();
    } catch (Throwable $e) {
        error_log($e->getMessage());
        $error = 'Unable to delete user account.';
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/nav.php';
?>

<main class="container py-4" style="max-width: 580px;">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-danger text-white py-3">
            <h1 class="h4 mb-0">Confirm User Account Deletion</h1>
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

                <dt class="col-sm-4 text-muted">Role / Status</dt>
                <dd class="col-sm-8">
                    <span class="badge <?= getRoleBadgeClass($targetUser['role']) ?>"><?= escapeHtml($targetUser['role']) ?></span>
                    <span class="badge <?= getStatusBadgeClass($targetUser['status']) ?> ms-1">
                        <?= escapeHtml($targetUser['status']) ?>
                    </span>
                </dd>
            </dl>

            <?php if ($hasRequests): ?>
                <!-- Retention Policy Rule Check (UM-08) -->
                <div class="alert alert-warning border-warning" role="alert">
                    <h5 class="alert-heading h6 fw-bold">Deletion Blocked by Data Retention Policy</h5>
                    <p class="mb-2">
                        This user account is associated with <strong><?= $requestCount ?></strong> tutoring request record(s).
                    </p>
                    <p class="mb-0 small">
                        Under the approved Peer Tutoring Hub retention policy, accounts with existing tutoring requests cannot be permanently deleted to preserve academic records and audit history. To suspend this user's access, deactivate the account instead.
                    </p>
                </div>
                <div class="d-flex gap-2 mt-4">
                    <a href="user-status.php?user_id=<?= (int)$targetId ?>" class="btn btn-warning">Go to Deactivate Account</a>
                    <a href="users.php" class="btn btn-outline-secondary">Return to Users</a>
                </div>
            <?php elseif ($isSelf): ?>
                <div class="alert alert-danger" role="alert">
                    You cannot delete your own active administrator account while logged in.
                </div>
                <a href="users.php" class="btn btn-outline-secondary">Back to Users</a>
            <?php elseif ($isLastAdmin): ?>
                <div class="alert alert-danger" role="alert">
                    This account is the only active administrator in the system and cannot be deleted.
                </div>
                <a href="users.php" class="btn btn-outline-secondary">Back to Users</a>
            <?php else: ?>
                <div class="alert alert-danger" role="alert">
                    <strong>Warning:</strong> Deleting this account is permanent. All account credentials will be removed immediately.
                </div>

                <form method="post" action="user-delete.php">
                    <input type="hidden" name="csrf_token" value="<?= escapeHtml(csrfToken()) ?>">
                    <input type="hidden" name="user_id" value="<?= (int)$targetId ?>">

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-danger px-3">Confirm Permanent Deletion</button>
                        <a href="users.php" class="btn btn-sm btn-outline-secondary px-3">Cancel</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

