<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../services/userService.php';
require_once __DIR__ . '/../services/requestService.php';

startSecureSession();

if (!empty($_SESSION['user_id'])) {
    header('Location: profile.php', true, 303);
    exit;
}

$error = '';
$fullName = '';
$email = '';
$role = 'Student';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST);

    $fullName = trim((string)($_POST['full_name'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $role = trim((string)($_POST['role'] ?? 'Student'));

    try {
        $pdo = openDatabase();
        registerUser($pdo, $_POST);
        header('Location: login.php?registered=1', true, 303);
        exit;
    } catch (InvalidArgumentException $e) {
        $error = $e->getMessage();
    } catch (Throwable $e) {
        error_log($e->getMessage());
        $error = 'Unable to complete registration. Please try again.';
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/nav.php';
?>

<main class="container py-4" style="max-width: 540px;">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h1 class="h3 card-title text-center mb-3">Create User Account</h1>
            <p class="text-center text-muted small mb-4">Register as a Student or Tutor to join the Peer Tutoring Hub</p>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger py-2" role="alert"><?= escapeHtml($error) ?></div>
            <?php endif; ?>

            <form method="post" action="register.php" novalidate>
                <input type="hidden" name="csrf_token" value="<?= escapeHtml(csrfToken()) ?>">

                <div class="mb-3">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" maxlength="120" value="<?= escapeHtml($fullName) ?>" required autofocus>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" maxlength="254" value="<?= escapeHtml($email) ?>" required>
                    <div class="form-text">Used for login and notifications. Must be unique.</div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" minlength="8" required>
                    <div class="form-text">Minimum 8 characters.</div>
                </div>

                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8" required>
                </div>

                <div class="mb-4">
                    <label class="form-label d-block">Account Role</label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="role" id="role_student" value="Student" <?= $role === 'Student' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="role_student">Student (request tutoring)</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="role" id="role_tutor" value="Tutor" <?= $role === 'Tutor' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="role_tutor">Tutor (provide tutoring)</label>
                    </div>
                    <div class="form-text">Note: Administrator accounts can only be provisioned by existing administrators.</div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">Create Account</button>
                </div>
            </form>

            <div class="text-center mt-3 pt-3 border-top">
                <span class="text-muted small">Already have an account?</span>
                <a href="login.php" class="small fw-semibold ms-1">Log In</a>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

