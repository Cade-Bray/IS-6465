<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../services/requestService.php';

$pdo = openDatabase();
$user = requireLogin($pdo);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/nav.php';
?>

<main class="container py-4" style="max-width: 650px;">
    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Your profile has been updated successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['pw_changed'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Your password has been changed successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0">My Profile</h1>
            <span class="badge <?= $user['status'] === 'Active' ? 'bg-success-subtle text-success-emphasis border border-success-subtle' : 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle' ?> fs-6">
                <?= escapeHtml($user['status']) ?>
            </span>
        </div>
        <div class="card-body p-4">
            <dl class="row mb-4">
                <dt class="col-sm-4 text-muted">Full Name</dt>
                <dd class="col-sm-8 fw-semibold"><?= escapeHtml($user['full_name']) ?></dd>

                <dt class="col-sm-4 text-muted">Email Address</dt>
                <dd class="col-sm-8"><?= escapeHtml($user['email']) ?></dd>

                <dt class="col-sm-4 text-muted">Account Role</dt>
                <dd class="col-sm-8">
                    <span class="badge bg-primary-subtle text-primary-emphasis border border-primary-subtle fs-6"><?= escapeHtml($user['role']) ?></span>
                    <small class="text-muted ms-2">(read-only)</small>
                </dd>

                <dt class="col-sm-4 text-muted">Member Since</dt>
                <dd class="col-sm-8"><?= escapeHtml($user['created_at'] ?? 'N/A') ?></dd>
            </dl>

            <div class="d-flex flex-wrap gap-2 pt-2 border-top">
                <a href="edit-profile.php" class="btn btn-primary">Edit Profile</a>
                <a href="change-password.php" class="btn btn-outline-secondary">Change Password</a>
            </div>
        </div>
    </div>

    <!-- Role-specific dashboard actions -->
    <div class="card shadow-sm border-0 bg-light">
        <div class="card-body p-4">
            <h5 class="card-title mb-3">Available Actions</h5>
            <?php if ($user['role'] === 'Student'): ?>
                <p class="text-muted small">As an active student, you can request new peer tutoring sessions and review or manage your submitted requests.</p>
                <div class="d-flex gap-2">
                    <a href="request-form.php" class="btn btn-success">Request a Tutoring Session</a>
                    <a href="requests.php" class="btn btn-outline-primary">View My Requests</a>
                </div>
            <?php elseif ($user['role'] === 'Administrator'): ?>
                <p class="text-muted small">As an administrator, you have full access to manage user accounts, assign roles, and review all student tutoring requests.</p>
                <div class="d-flex gap-2">
                    <a href="users.php" class="btn btn-dark">Manage User Accounts</a>
                    <a href="requests.php" class="btn btn-outline-primary">View All Tutoring Requests</a>
                </div>
            <?php elseif ($user['role'] === 'Tutor'): ?>
                <div class="alert alert-info mb-0">
                    <strong>Tutor Account Notice:</strong> Your tutor account is active. In this version (Lab 7), tutor assignment workflows are under development. You may maintain your profile and change your password.
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

