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
        <span class="brand-subtitle">Tata Steel Technical Training</span>
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
            <span class="user-avatar"><?php echo strtoupper(substr($userName, 0, 1)); ?></span>
            <span class="user-display-name"><?php echo htmlspecialchars($userName); ?></span>
            <span class="dropdown-chevron">▾</span>
          </button>
          <div class="dropdown-menu" id="userDropdownMenu">
            <div class="dropdown-header">
              <strong><?php echo htmlspecialchars($userName); ?></strong>
              <small><?php echo ucfirst($userRole); ?></small>
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

<style>
.navbar-header {
  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border-bottom: 1px solid var(--border);
  position: sticky;
  top: 0;
  z-index: 1000;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
}
.navbar-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 12px 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.brand-link {
  display: flex;
  align-items: center;
  gap: 14px;
  text-decoration: none;
  color: inherit;
}
.brand-logo {
  height: 42px;
  width: auto;
  object-fit: contain;
  border-radius: 6px;
}
.brand-text {
  display: flex;
  flex-direction: column;
}
.brand-title {
  font-size: 1.15rem;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: -0.01em;
}
.brand-subtitle {
  font-size: 0.72rem;
  font-weight: 600;
  color: var(--primary);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.nav-links {
  display: flex;
  align-items: center;
  gap: 20px;
}
.nav-link {
  font-size: 0.925rem;
  font-weight: 600;
  color: #475569;
  text-decoration: none;
  transition: var(--transition);
  padding: 6px 10px;
  border-radius: var(--radius-sm);
}
.nav-link:hover {
  color: var(--primary);
  background: var(--primary-light);
}
.nav-user-dropdown {
  position: relative;
}
.user-menu-btn {
  display: flex;
  align-items: center;
  gap: 10px;
  background: #f8fafc;
  border: 1px solid var(--border);
  padding: 6px 14px 6px 8px;
  border-radius: var(--radius-full);
  cursor: pointer;
  font-family: inherit;
  transition: var(--transition);
}
.user-menu-btn:hover {
  background: #f1f5f9;
  border-color: #cbd5e1;
}
.user-avatar {
  width: 32px;
  height: 32px;
  background: var(--primary-gradient);
  color: #fff;
  border-radius: var(--radius-full);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.875rem;
}
.user-display-name {
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--text-main);
}
.dropdown-chevron {
  font-size: 0.75rem;
  color: var(--text-muted);
}
.dropdown-menu {
  display: none;
  position: absolute;
  right: 0;
  top: calc(100% + 8px);
  width: 220px;
  background: #ffffff;
  border-radius: var(--radius-md);
  border: 1px solid var(--border);
  box-shadow: var(--shadow-lg);
  padding: 8px 0;
  z-index: 1050;
  animation: slideDown 0.15s ease-out;
}
.dropdown-menu.show {
  display: block;
}
.dropdown-header {
  padding: 10px 18px 8px;
  border-bottom: 1px solid var(--border);
  display: flex;
  flex-direction: column;
}
.dropdown-header strong {
  font-size: 0.9rem;
  color: var(--text-main);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.dropdown-header small {
  color: var(--text-muted);
  font-size: 0.75rem;
  text-transform: uppercase;
  font-weight: 700;
  letter-spacing: 0.04em;
}
.dropdown-item {
  display: block;
  padding: 10px 18px;
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--text-main);
  text-decoration: none;
  transition: var(--transition);
}
.dropdown-item:hover {
  background: #f8fafc;
  color: var(--primary);
}
.dropdown-divider {
  height: 1px;
  background: var(--border);
  margin: 6px 0;
}
.dropdown-item.text-danger {
  color: var(--danger);
}
.dropdown-item.text-danger:hover {
  background: var(--danger-bg);
}
@keyframes slideDown {
  from { opacity: 0; transform: translateY(-6px); }
  to { opacity: 1; transform: translateY(0); }
}
@media (max-width: 768px) {
  .brand-subtitle { display: none; }
  .user-display-name { display: none; }
  .nav-links { gap: 10px; }
}
</style>

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
