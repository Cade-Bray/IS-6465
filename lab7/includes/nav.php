<?php
$isLoggedIn = !empty($_SESSION['user_id']);
$userRole = $_SESSION['role'] ?? '';
$userName = $_SESSION['display_name'] ?? '';
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm mb-4">
  <div class="container">
    <a class="navbar-brand fw-bold" href="../index.php">Peer Tutoring Hub</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav me-auto">
        <?php if ($isLoggedIn): ?>
          <li class="nav-item">
            <a class="nav-link" href="profile.php">My Profile</a>
          </li>
          <?php if ($userRole === 'Student'): ?>
            <li class="nav-item">
              <a class="nav-link" href="request-form.php">Request Tutoring</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="requests.php">My Requests</a>
            </li>
          <?php elseif ($userRole === 'Administrator'): ?>
            <li class="nav-item">
              <a class="nav-link" href="requests.php">Tutoring Requests</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="users.php">Manage Users</a>
            </li>
          <?php endif; ?>
        <?php endif; ?>
      </ul>
      <ul class="navbar-nav ms-auto align-items-lg-center">
        <?php if ($isLoggedIn): ?>
          <li class="nav-item me-lg-3 text-light py-1 py-lg-0">
            <span class="small">Signed in as <strong><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></strong> <span class="badge bg-light text-dark ms-1"><?= htmlspecialchars($userRole, ENT_QUOTES, 'UTF-8') ?></span></span>
          </li>
          <li class="nav-item">
            <a class="btn btn-sm btn-outline-light" href="logout.php">Log Out</a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link" href="login.php">Log In</a>
          </li>
          <li class="nav-item">
            <a class="btn btn-sm btn-outline-light ms-lg-2" href="register.php">Register</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
