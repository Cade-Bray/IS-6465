<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../services/userService.php';
require_once __DIR__ . '/../services/requestService.php';

$pdo = openDatabase();
$user = requireLogin($pdo);

$error = '';
$fullName = $user['full_name'];
$email = $user['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST);

    $fullName = trim((string)($_POST['full_name'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));

    try {
        updateProfile($pdo, (int)$user['user_id'], $_POST);
        $_SESSION['display_name'] = $fullName;
        header('Location: profile.php?updated=1', true, 303);
        exit;
    } catch (InvalidArgumentException $e) {
        $error = $e->getMessage();
    } catch (Throwable $e) {
        error_log($e->getMessage());
        $error = 'Unable to update profile. Please try again.';
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/nav.php';
?>

<main class="container py-4" style="max-width: 540px;">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 border-bottom">
            <h1 class="h4 mb-0">Edit Profile</h1>
        </div>
        <div class="card-body p-4">
            <?php if ($error !== ''): ?>
                <div class="alert alert-danger py-2" role="alert"><?= escapeHtml($error) ?></div>
            <?php endif; ?>

            <form method="post" action="edit-profile.php" novalidate>
                <input type="hidden" name="csrf_token" value="<?= escapeHtml(csrfToken()) ?>">

                <div class="mb-3">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" maxlength="120" value="<?= escapeHtml($fullName) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" maxlength="254" value="<?= escapeHtml($email) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Role</label>
                    <input type="text" class="form-control bg-light" value="<?= escapeHtml($user['role']) ?>" readonly disabled>
                    <div class="form-text">Role cannot be altered through profile editing.</div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Account Status</label>
                    <input type="text" class="form-control bg-light" value="<?= escapeHtml($user['status']) ?>" readonly disabled>
                    <div class="form-text">Account status is managed administratively.</div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="profile.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

