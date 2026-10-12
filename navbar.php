<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = $pageTitle ?? "Portal";
$isLoggedIn = !empty($_SESSION['user_id']);
$userRole = $_SESSION['role'] ?? 'user';
$userName = $_SESSION['name'] ?? 'User';
$dashboardUrl = ($userRole === 'admin') ? 'admin_dashboard.php' : 'user_dashboard.php';
?>

<header class="navbar-header">
  <div class="navbar-container">
    <a href="<?php echo $isLoggedIn ? $dashboardUrl : 'index.html'; ?>" class="brand-link">
      <img src="assets/images/logo.png" alt="SNTI Logo" class="brand-logo" onerror="this.style.display='none'">
      <div class="brand-text">
        <span class="brand-title">SNTI Feedback Portal</span>
        <span class="brand-subtitle">Tata Steel Technical Education</span>
      </div>
    </a>

    <nav class="nav-links">
      <?php if ($isLoggedIn): ?>
        <a href="<?php echo $dashboardUrl; ?>" class="nav-link">Dashboard</a>
        <?php if ($userRole === 'admin'): ?>
          <a href="view_feedbacks.php" class="nav-link">Feedbacks</a>
          <a href="view_users.php" class="nav-link">Trainees</a>
          <a href="view_signed_up_users.php" class="nav-link">Signups</a>
        <?php endif; ?>
        <div class="nav-user-dropdown">
          <button class="user-menu-btn" id="userMenuBtn" type="button" aria-expanded="false">
            <span class="user-avatar" style="<?php echo ($userRole === 'admin') ? 'background: var(--admin-gradient);' : ''; ?>">
              <?php echo strtoupper(substr($userName, 0, 1)); ?>
            </span>
            <span class="user-display-name"><?php echo htmlspecialchars($userName); ?></span>
            <span class="dropdown-chevron">▾</span>
          </button>
          <div class="dropdown-menu" id="userDropdownMenu">
            <div class="dropdown-header">
              <strong><?php echo htmlspecialchars($userName); ?></strong>
              <small><?php echo ($userRole === 'admin') ? '🛡️ Administrator' : '🎓 Trainee'; ?></small>
            </div>
            <a href="profile.php" class="dropdown-item">Profile & Settings</a>
            <div class="dropdown-divider"></div>
            <a href="logout.php" class="dropdown-item text-danger">Sign Out</a>
          </div>
        </div>
      <?php else: ?>
        <a href="login.php" class="nav-link">Log In</a>
        <a href="signup.php" class="btn btn-primary" style="padding: 8px 18px; font-size: 0.875rem;">Create Account</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const btn = document.getElementById('userMenuBtn');
  const menu = document.getElementById('userDropdownMenu');
  if (btn && menu) {
    btn.addEventListener('click', function(e) {
      e.stopPropagation();
      menu.classList.toggle('show');
    });
    document.addEventListener('click', function(e) {
      if (!btn.contains(e.target) && !menu.contains(e.target)) {
        menu.classList.remove('show');
      }
    });
  }
});
</script>
