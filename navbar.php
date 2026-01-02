<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($pageTitle)) {
    $pageTitle = "Dashboard";
}

$dashboardLink = "#";
if (isset($_SESSION['role'])) {
    $dashboardLink = $_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php';
}
?>

<header class="navbar">
  <div class="logo-title">
    <img src="dbb52941-0433-4d12-be04-f76574e028cd.png" alt="Tata Prashikshan Logo" class="logo">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
  </div>
  <div class="profile-dropdown">
    <button class="profile-button">Menu ▼</button>
    <div class="dropdown-content">
      <a href="<?php echo $dashboardLink; ?>">Dashboard</a>
      <a href="profile.php">Profile</a>
      <a href="settings.php">Settings</a>
      <a href="logout.php">Logout</a>
    </div>
  </div>
</header>

<style>
  .navbar {
    background-color: #517891;
    padding: 14px 28px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: #fff;
    font-family: Arial, sans-serif;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
  }

  .logo-title {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .logo-title img.logo {
    height: 40px;
    width: auto;
    vertical-align: middle;
  }

  .navbar h1 {
    font-size: 24px;
    font-weight: bold;
    margin: 0;
  }

  .profile-dropdown {
    position: relative;
    display: inline-block;
  }

  .profile-button {
    background: none;
    border: none;
    color: white;
    font-size: 18px;
    cursor: pointer;
    padding: 8px 12px;
  }

  .dropdown-content {
    display: none;
    position: absolute;
    right: 0;
    background-color: white;
    min-width: 170px;
    box-shadow: 0px 6px 16px rgba(0, 0, 0, 0.2);
    z-index: 100;
    border-radius: 6px;
    overflow: hidden;
    transition: all 0.3s ease;
  }

  .dropdown-content a {
    color: #517891;
    padding: 12px 18px;
    text-decoration: none;
    display: block;
    font-weight: 500;
    transition: background 0.2s ease;
  }

  .dropdown-content a:hover {
    background-color: #f0f0f0;
  }

  .profile-dropdown:hover .dropdown-content {
    display: block;
  }

  @media (max-width: 600px) {
    .navbar h1 {
      font-size: 18px;
    }
    .profile-button {
      font-size: 16px;
    }
  }
</style>
