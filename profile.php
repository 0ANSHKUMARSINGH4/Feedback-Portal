<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

// Handle profile image upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["profile_image"])) {
    $targetDir = "uploads/";
    $fileName = basename($_FILES["profile_image"]["name"]);
    $targetFilePath = $targetDir . $fileName;
    $imageFileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array($imageFileType, $allowedTypes)) {
        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $targetFilePath)) {
            $update = $conn->prepare("UPDATE users SET profile_image = ? WHERE user_id = ?");
            $update->bind_param("ss", $fileName, $user_id);
            $update->execute();
            $update->close();
            $message = "Profile image updated successfully.";
        }
    }
}

// Handle password update
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['current_password'], $_POST['new_password'], $_POST['confirm_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $message = "New passwords do not match.";
    } else {
        $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $stmt->bind_result($hashed_password);
        $stmt->fetch();
        $stmt->close();

        if (password_verify($current_password, $hashed_password)) {
            $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->bind_param("ss", $new_hashed, $user_id);
            $message = $stmt->execute() ? "Password updated successfully." : "Failed to update password.";
            $stmt->close();
        } else {
            $message = "Current password is incorrect.";
        }
    }
}

// Handle email update
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['new_email'])) {
    $new_email = $_POST['new_email'];

    // Check if email already exists for another user
    $check = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $check->bind_param("ss", $new_email, $user_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $message = "Email is already in use.";
    } else {
        $stmt = $conn->prepare("UPDATE users SET email = ? WHERE user_id = ?");
        $stmt->bind_param("ss", $new_email, $user_id);
        if ($stmt->execute()) {
            $_SESSION['email'] = $new_email;
            $message = "Email updated successfully.";
        } else {
            $message = "Failed to update email.";
        }
        $stmt->close();
    }
    $check->close();
}

// Fetch user data
$sql = "SELECT name, profile_image, created_at, email FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$stmt->bind_result($name, $profile_image, $created_at, $email);
$stmt->fetch();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Profile Page</title>
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: linear-gradient(to right, #90D5FF, #77B1D4);
    }

    .container {
      max-width: 500px;
      background: white;
      margin: 40px auto;
      padding: 30px;
      border-radius: 10px;
      text-align: center;
    }

    .container h2 {
      color: #517891;
    }

    .profile-img {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid #517891;
      margin-bottom: 20px;
    }

    input[type="file"],
    input[type="email"],
    input[type="password"] {
      padding: 8px;
      width: 80%;
      margin-bottom: 10px;
      border: 1px solid #ccc;
      border-radius: 5px;
    }

    .btn {
      margin-top: 15px;
      padding: 10px 20px;
      background-color: #57B9FF;
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: bold;
    }

    .btn:hover {
      background-color: #517891;
    }

    .link-btn {
      display: block;
      margin-top: 20px;
      color: #517891;
      text-decoration: none;
      font-weight: bold;
    }

    .link-btn:hover {
      text-decoration: underline;
    }

    .message {
      color: green;
      font-weight: bold;
      margin-bottom: 20px;
    }
  </style>
</head>
<body>

<?php
$pageTitle = "Profile";
include 'navbar.php';
?>

<div class="container">
  <h2>Profile Page</h2>
  <?php if (!empty($message)): ?>
    <div class="message"><?php echo htmlspecialchars($message); ?></div>
  <?php endif; ?>

  <hr>
  <p><strong>User ID:</strong> <?php echo htmlspecialchars($user_id); ?></p>
  <p><strong>Name:</strong> <?php echo htmlspecialchars($name); ?></p>
  <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
  <p><strong>Account Created:</strong> <?php echo date("d M Y, h:i A", strtotime($created_at)); ?></p>

  <?php if (!empty($profile_image)): ?>
    <img src="uploads/<?php echo htmlspecialchars($profile_image); ?>" alt="Profile Image" class="profile-img">
  <?php else: ?>
    <img src="https://via.placeholder.com/120" alt="Default Image" class="profile-img">
  <?php endif; ?>

  <hr>

  <form action="profile.php" method="post" enctype="multipart/form-data">
    <label>Change Profile Image:</label><br>
    <input type="file" name="profile_image" accept="image/*" required>
    <br>
    <input type="submit" class="btn" value="Upload Image">
  </form>

  <hr>

  <h3>Change Password</h3>
  <form method="POST">
    <label for="current_password">Current Password:</label><br>
    <input type="password" name="current_password" required><br>

    <label for="new_password">New Password:</label><br>
    <input type="password" name="new_password" required><br>

    <label for="confirm_password">Confirm New Password:</label><br>
    <input type="password" name="confirm_password" required><br>

    <input type="submit" class="btn" value="Change Password">
  </form>

  <hr>

  <h3>Change Email</h3>
  <form method="POST">
    <input type="email" name="new_email" value="<?php echo htmlspecialchars($email); ?>" required>
    <input type="submit" class="btn" value="Update Email">
  </form>

  <hr>
  <a class="link-btn" href="logout.php">Logout</a>
</div>

</body>
</html>
