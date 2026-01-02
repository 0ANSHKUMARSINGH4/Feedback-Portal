<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

include 'db_connection.php';

$user_id = $_SESSION['user_id'];

$sql = "SELECT name, created_at FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$stmt->bind_result($name, $created_at);
$stmt->fetch();
$stmt->close();

$feedback_count = $conn->query("SELECT COUNT(*) as total FROM feedback")->fetch_assoc()['total'];
$signup_count = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
$registered_count = $conn->query("SELECT COUNT(*) as total FROM complete_info")->fetch_assoc()['total'];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: linear-gradient(to right, #90D5FF, #77B1D4);
    }

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

    .logo-title img {
      height: 40px;
      width: auto;
    }

    .navbar h1 {
      font-size: 22px;
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
      font-size: 16px;
      cursor: pointer;
    }

    .dropdown-content {
      display: none;
      position: absolute;
      right: 0;
      background-color: #fff;
      min-width: 160px;
      box-shadow: 0px 8px 16px rgba(0,0,0,0.2);
      z-index: 1;
      border-radius: 6px;
    }

    .dropdown-content a {
      color: #517891;
      padding: 12px 16px;
      text-decoration: none;
      display: block;
    }

    .dropdown-content a:hover {
      background-color: #f1f1f1;
    }

    .profile-dropdown:hover .dropdown-content {
      display: block;
    }

    .dashboard {
      max-width: 700px;
      margin: 40px auto;
      background: #fff;
      padding: 30px;
      border-radius: 10px;
      text-align: center;
    }

    .btn {
      display: inline-block;
      margin: 10px;
      padding: 12px 24px;
      background: #57B9FF;
      color: white;
      text-decoration: none;
      border-radius: 8px;
      font-weight: bold;
      border: none;
      cursor: pointer;
    }

    .btn:hover {
      background-color: #517891;
    }

    .info-box {
      margin: 20px 0;
      background: #f0f8ff;
      padding: 20px;
      border-radius: 10px;
      text-align: center;
    }

    .info-box h3 {
      margin-top: 0;
      color: #333;
      font-size: 22px;
    }

    .info-box p {
      margin: 10px 0;
      font-size: 18px;
      font-weight: bold;
    }

    .report-download {
      margin-top: 30px;
    }
  </style>
</head>
<body>

<!-- Navbar with logo -->
<div class="navbar">
  <div class="logo-title">
    <img src="dbb52941-0433-4d12-be04-f76574e028cd.png" alt="Tata Prashikshan Logo">
    <h1>Admin Dashboard</h1>
  </div>
  <div class="profile-dropdown">
    <button class="profile-button">Menu ▼</button>
    <div class="dropdown-content">
      <a href="profile.php">Profile</a>
      <a href="settings.php">Settings</a>
      <a href="logout.php">Logout</a>
    </div>
  </div>
</div>

<!-- Dashboard Content -->
<div class="dashboard">
  <h2>Welcome, Admin!</h2>
  <p><strong>Name:</strong> <?php echo htmlspecialchars($name); ?></p>
  <p><strong>User ID:</strong> <?php echo htmlspecialchars($user_id); ?></p>
  <p>Account Created: <?php echo htmlspecialchars($created_at); ?></p>

  <!-- Signups -->
  <div class="info-box">
    <h3>Total Signups</h3>
    <p><?php echo $signup_count; ?> users</p>
    <form action="view_signed_up_users.php" method="get">
      <button class="btn">View Signups</button>
    </form>
  </div>

  <!-- Registered Trainees -->
  <div class="info-box">
    <h3>Registered Trainees</h3>
    <p><?php echo $registered_count; ?> trainees</p>
    <form action="view_users.php" method="get">
      <button class="btn">View Trainees</button>
    </form>
  </div>

  <!-- Feedback -->
  <div class="info-box">
    <h3>Feedbacks Received</h3>
    <p><?php echo $feedback_count; ?> responses</p>
    <form action="view_feedbacks.php" method="get">
      <button class="btn">View Feedbacks</button>
    </form>
  </div>

  <!-- Report Download -->
  <div class="report-download">
    <form action="generate_report.php" method="get">
      <button class="btn" style="width: 300px;">Download Full Report</button>
    </form>
  </div>
</div>

</body>
</html>
