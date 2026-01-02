<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
//$email = $_SESSION['email'] ?? ''; // fallback if not set

// Fetch user name and created_at
$sql = "SELECT name, created_at FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$stmt->bind_result($name, $created_at);
$stmt->fetch();
$stmt->close();

// Check if user has registered
$is_registered = false;
$sql = "SELECT id FROM complete_info WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $is_registered = true;
}
$stmt->close();


// Fetch feedback if submitted (based on user_id, which is correct)
$has_feedback = false;
$feedback_data = null;

$sql = "SELECT department, feedback_message, q1, q2, q3, q4, q5, submitted_at FROM feedback WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $has_feedback = true;
    $feedback_data = $result->fetch_assoc();
}
$stmt->close();
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Trainee Dashboard</title>
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: linear-gradient(to right, #90D5FF, #77B1D4);
    }
    .navbar {
      background-color: #517891;
      padding: 10px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      color: #fff;
    }
    .navbar h1 {
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
    }
    .btn:hover {
      background-color: #517891;
    }
    .btn.disabled {
      background-color: #ccc;
      pointer-events: none;
      cursor: default;
    }
    .status {
      margin-top: 10px;
      font-weight: bold;
      color: #2d5d89;
    }
    .feedback-box {
      margin-top: 30px;
      background: #f0f8ff;
      border: 1px solid #ccc;
      padding: 20px;
      border-radius: 10px;
      text-align: left;
    }
    .feedback-box h3 {
      margin-top: 0;
      color: #333;
    }
    .feedback-box p {
      margin: 8px 0;
    }
  </style>
</head>
<body>

<!-- Navbar -->
<div class="navbar">
  <div style="display: flex; align-items: center;">
    <img src="dbb52941-0433-4d12-be04-f76574e028cd.png" alt="Logo" style="height: 40px; margin-right: 12px;">
    <h1>Trainee Portal</h1>
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
  <h2>Welcome, <?php echo htmlspecialchars($name); ?>!</h2>
  <p><strong>User ID:</strong> <?php echo htmlspecialchars($user_id); ?></p>
  <p><strong>Account Created:</strong> <?php echo htmlspecialchars($created_at); ?></p>
  <br>

  <p><strong>1. Register as a Trainee</strong></p>
  <?php if ($is_registered): ?>
    <a class="btn disabled">Already Registered</a>
    <div class="status">You have already completed registration.</div>
  <?php else: ?>
    <a href="registration.php" class="btn">Registration Form</a>
  <?php endif; ?>

  <br><br>

  <p><strong>2. Submit Feedback</strong></p>
<?php if (!$is_registered): ?>
  <a class="btn disabled">Feedback Disabled</a>
  <div class="status">Please register as a trainee to enable feedback submission.</div>
<?php elseif ($has_feedback): ?>
  <a class="btn disabled">Feedback Submitted</a>
  <div class="status">You have already submitted your feedback.</div>
<?php else: ?>
  <a href="feedback_form.php" class="btn">Feedback Page</a>
<?php endif; ?>

  <?php if ($has_feedback && $feedback_data): 
    $ratings = [
    'Q1' => $feedback_data['q1'],
    'Q2' => $feedback_data['q2'],
    'Q3' => $feedback_data['q3'],
    'Q4' => $feedback_data['q4'],
    'Q5' => $feedback_data['q5'],
];
  ?>
  <div class="feedback-box">
    <h3>Your Submitted Feedback</h3>
    <p><strong>Department:</strong> <?php echo htmlspecialchars($feedback_data['department']); ?></p>
    <p><strong>Feedback Message:</strong> <?php echo nl2br(htmlspecialchars($feedback_data['feedback_message'])); ?></p>
    <p><strong>Submitted On:</strong> <?php echo htmlspecialchars($feedback_data['submitted_at']); ?></p>
    <?php if (is_array($ratings)): ?>
      <h4>Ratings (1 to 5)</h4>
      <?php foreach ($ratings as $q => $rating): ?>
  <p><?php echo strtoupper($q); ?>: ⭐ <?php echo htmlspecialchars($rating); ?></p>
<?php endforeach; ?>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>

</body>
</html>
