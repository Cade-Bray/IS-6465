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
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST);
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    $pdo = openDatabase();
    $user = authenticateUser($pdo, $email, $password);

    if ($user) {
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['user_id'];
        $_SESSION['display_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['last_activity'] = time();
        csrfToken();

        header('Location: profile.php', true, 303);
        exit;
    } else {
        $error = 'Invalid email or password.';
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/nav.php';
?>

<main class="container py-4" style="max-width: 480px;">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4 p-md-5">
            <h1 class="h3 card-title text-center text-dark fw-bold mb-4">Log In to Peer Tutoring Hub</h1>

            <?php if (isset($_GET['logged_out'])): ?>
                <div class="alert alert-info py-2" role="alert">You have been logged out successfully.</div>
            <?php endif; ?>

            <?php if (isset($_GET['expired'])): ?>
                <div class="alert alert-warning py-2" role="alert">Your session expired due to 20 minutes of inactivity. Please log in again.</div>
            <?php endif; ?>

            <?php if (isset($_GET['inactive'])): ?>
                <div class="alert alert-danger py-2" role="alert">Your account is currently inactive. Please contact an administrator.</div>
            <?php endif; ?>

            <?php if (isset($_GET['registered'])): ?>
                <div class="alert alert-success py-2" role="alert">Account registered successfully! You may now log in.</div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger py-2" role="alert"><?= escapeHtml($error) ?></div>
            <?php endif; ?>

            <form method="post" action="login.php" class="stacked-form" novalidate>
                <input type="hidden" name="csrf_token" value="<?= escapeHtml(csrfToken()) ?>">

                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold text-secondary">Email address</label>
                    <input type="email" class="form-control form-control-lg" id="email" name="email" value="<?= escapeHtml($email) ?>" placeholder="name@example.edu" required autofocus>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold text-secondary">Password</label>
                    <input type="password" class="form-control form-control-lg" id="password" name="password" placeholder="Enter your password" required>
                </div>

                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-primary btn-lg fw-semibold">Log In</button>
                </div>
            </form>

            <div class="text-center mt-4 pt-3 border-top">
                <span class="text-muted small">Need an account?</span>
                <a href="register.php" class="small fw-semibold ms-1">Register as a Student or Tutor</a>
            </div>
        </div>
    </div>

    <div class="card mt-3 bg-light border-0 shadow-sm">
        <div class="card-body p-3">
            <h6 class="card-subtitle text-muted mb-2">Classroom Demo Accounts:</h6>
            <ul class="list-unstyled mb-0 small text-secondary">
                <li><strong>Admin:</strong> <code>admin@example.edu</code> / <code>AdminPass123!</code></li>
                <li><strong>Student:</strong> <code>student@example.edu</code> / <code>StudentPass123!</code></li>
                <li><strong>Tutor:</strong> <code>tutor@example.edu</code> / <code>TutorPass123!</code></li>
            </ul>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

