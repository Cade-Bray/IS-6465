<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../services/userService.php';
require_once __DIR__ . '/../services/requestService.php';

$pdo = openDatabase();
$currentUser = requireLogin($pdo);
requireRole($currentUser, ['Administrator']);

$search = trim((string)($_GET['search'] ?? ''));
$role = trim((string)($_GET['role'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));

$users = listUsers($pdo, $search, $role, $status);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/nav.php';
?>

<main class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Manage User Accounts</h1>
        <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle fs-6"><?= count($users) ?> account(s)</span>
    </div>

    <?php if (isset($_GET['role_updated'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            User role was updated successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['status_updated'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Account status was updated successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            User account was permanently deleted.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-3">
            <form method="get" action="users.php" class="d-flex flex-wrap align-items-center justify-content-center gap-2">
                <div style="min-width: 240px; max-width: 320px;" class="flex-grow-1 flex-sm-grow-0">
                    <input type="text" class="form-control" name="search" placeholder="Search by name or email..." value="<?= escapeHtml($search) ?>" aria-label="Search users">
                </div>

                <select class="form-select w-auto" name="role" aria-label="Filter by Role" style="min-width: 170px;">
                    <option value="">All Roles</option>
                    <option value="Student" <?= $role === 'Student' ? 'selected' : '' ?>>Student</option>
                    <option value="Tutor" <?= $role === 'Tutor' ? 'selected' : '' ?>>Tutor</option>
                    <option value="Administrator" <?= $role === 'Administrator' ? 'selected' : '' ?>>Administrator</option>
                </select>

                <select class="form-select w-auto" name="status" aria-label="Filter by Status" style="min-width: 160px;">
                    <option value="">All Statuses</option>
                    <option value="Active" <?= $status === 'Active' ? 'selected' : '' ?>>Active</option>
                    <option value="Inactive" <?= $status === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-3 fw-semibold">Filter</button>
                    <?php if ($search !== '' || $role !== '' || $status !== ''): ?>
                        <a href="users.php" class="btn btn-outline-secondary px-3">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col" style="width: 70px;">ID</th>
                        <th scope="col">Name</th>
                        <th scope="col">Email</th>
                        <th scope="col">Role</th>
                        <th scope="col">Status</th>
                        <th scope="col">Created</th>
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                No matching accounts found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?= (int)$u['user_id'] ?></td>
                                <td class="fw-semibold"><?= escapeHtml($u['full_name']) ?></td>
                                <td><?= escapeHtml($u['email']) ?></td>
                                <td>
                                    <?php
                                    $roleBadge = match ($u['role']) {
                                        'Administrator' => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle',
                                        'Tutor' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
                                        default => 'bg-primary-subtle text-primary-emphasis border border-primary-subtle',
                                    };
                                    ?>
                                    <span class="badge <?= $roleBadge ?>"><?= escapeHtml($u['role']) ?></span>
                                </td>
                                <td>
                                    <?php
                                    $statusBadge = match ($u['status']) {
                                        'Active' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
                                        default => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
                                    };
                                    ?>
                                    <span class="badge <?= $statusBadge ?>"><?= escapeHtml($u['status']) ?></span>
                                </td>
                                <td class="small text-muted"><?= escapeHtml($u['created_at']) ?></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="user-role.php?user_id=<?= (int)$u['user_id'] ?>" class="btn btn-outline-secondary" title="Change Role">Role</a>
                                        <a href="user-status.php?user_id=<?= (int)$u['user_id'] ?>" class="btn btn-outline-secondary" title="Toggle Status">Status</a>
                                        <a href="user-delete.php?user_id=<?= (int)$u['user_id'] ?>" class="btn btn-outline-danger" title="Delete Account">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

