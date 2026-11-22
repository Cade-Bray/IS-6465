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

    <div class="card shadow-sm border-0 mb-5 overflow-hidden">
        <?php if (empty($users)): ?>
            <div class="card-body p-5 text-center text-muted">
                <div class="mb-3">
                    <span class="d-inline-block p-3 rounded-circle bg-light text-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="currentColor" class="bi bi-inbox" viewBox="0 0 16 16">
                            <path d="M4.98 4a.5.5 0 0 0-.39.188L1.54 8H6a.5.5 0 0 1 .5.5 1.5 1.5 0 1 0 3 0A.5.5 0 0 1 10 8h4.46l-3.05-3.812A.5.5 0 0 0 11.02 4zm9.954 5H10.45a2.5 2.5 0 0 1-4.9 0H1.066l.32 2.562a.5.5 0 0 0 .497.438h12.234a.5.5 0 0 0 .496-.438zM3.809 3.563A1.5 1.5 0 0 1 4.981 3h6.038a1.5 1.5 0 0 1 1.172.563l3.7 4.625a1 1 0 0 1 .109 1.036l-1 8A1 1 0 0 1 14 18H2a1 1 0 0 1-.992-.876l-1-8a1 1 0 0 1 .11-1.036z"/>
                        </svg>
                    </span>
                </div>
                <h5 class="fw-semibold text-dark">No matching accounts found</h5>
                <p class="small text-muted mb-0">No user accounts match the selected filters.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0 w-100">
                    <thead class="table-light">
                        <tr class="text-secondary small fw-semibold">
                            <th scope="col" style="width: 80px;" class="ps-4">ID</th>
                            <th scope="col" style="min-width: 180px;">Name</th>
                            <th scope="col">Email</th>
                            <th scope="col" style="width: 130px;">Role</th>
                            <th scope="col" style="width: 120px;">Status</th>
                            <th scope="col" style="width: 160px;">Created</th>
                            <th scope="col" class="text-end pe-4" style="width: 170px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td class="ps-4">
                                    <span class="font-monospace fw-semibold text-secondary">#<?= (int)$u['user_id'] ?></span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= escapeHtml($u['full_name']) ?></div>
                                </td>
                                <td>
                                    <span class="text-secondary"><?= escapeHtml($u['email']) ?></span>
                                </td>
                                <td>
                                    <span class="badge <?= getRoleBadgeClass($u['role']) ?> px-3 py-1"><?= escapeHtml($u['role']) ?></span>
                                </td>
                                <td>
                                    <span class="badge <?= getStatusBadgeClass($u['status']) ?> px-3 py-1"><?= escapeHtml($u['status']) ?></span>
                                </td>
                                <td>
                                    <span class="small text-muted"><?= escapeHtml($u['created_at']) ?></span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="user-role.php?user_id=<?= (int)$u['user_id'] ?>" class="btn btn-outline-secondary" title="Change Role">Role</a>
                                        <a href="user-status.php?user_id=<?= (int)$u['user_id'] ?>" class="btn btn-outline-secondary" title="Toggle Status">Status</a>
                                        <a href="user-delete.php?user_id=<?= (int)$u['user_id'] ?>" class="btn btn-outline-danger" title="Delete Account">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white border-top py-3 px-4 d-flex justify-content-between align-items-center text-muted small">
                <span>Showing <strong><?= count($users) ?></strong> user account<?= count($users) === 1 ? '' : 's' ?></span>
                <span>Administrator System Access</span>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

