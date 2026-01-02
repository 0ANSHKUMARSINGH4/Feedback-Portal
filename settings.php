<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

include 'db_connection.php';
$user_id = $_SESSION['user_id'];
$message = "";

// Fetch current email
$email = "";
$stmt = $conn->prepare("SELECT email FROM users WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$stmt->bind_result($email);
$stmt->fetch();
$stmt->close();

// Handle email update
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['email']) && !isset($_POST['delete_account'])) {
    $new_email = $_POST['email'];

    if ($new_email !== $email) {
        // Check if new email already exists
        $check = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $check->bind_param("s", $new_email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = "Email already in use by another account.";
        } else {
            // Update users table
            $stmt = $conn->prepare("UPDATE users SET email = ? WHERE user_id = ?");
            $stmt->bind_param("ss", $new_email, $user_id);
            if ($stmt->execute()) {
                // Update complete_info table
                $update = $conn->prepare("UPDATE complete_info SET email = ? WHERE email = ?");
                $update->bind_param("ss", $new_email, $email);
                $update->execute();
                $update->close();

                // $_SESSION['user_id'] = $new_email;
                $message = "Email updated successfully.";
                $email = $new_email;
            } else {
                $message = "Failed to update email.";
            }
            $stmt->close();
        }
        $check->close();
    } else {
        $message = "New email is the same as current email.";
    }
}

// Handle profile picture update
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["profile_image"])) {
    $target_dir = "uploads/";
    $file_name = basename($_FILES["profile_image"]["name"]);
    $target_file = $target_dir . $file_name;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    $check = getimagesize($_FILES["profile_image"]["tmp_name"]);
    if ($check !== false && in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif']) &&
        move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {

        $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE user_id = ?");
        $stmt->bind_param("ss", $file_name, $user_id);
        $message = $stmt->execute() ? "Profile picture updated successfully." : "Failed to update profile picture.";
        $stmt->close();
    } else {
        $message = "Invalid image file or upload error.";
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

// Handle account deletion
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_account'])) {
    // Step 1: Delete feedback
    $stmt = $conn->prepare("DELETE FROM feedback WHERE user_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $stmt->close();

    // Step 2: Delete complete_info (match by email)
    $stmt = $conn->prepare("DELETE FROM complete_info WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->close();

    // Step 3: Delete from users
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("s", $user_id);
    if ($stmt->execute()) {
        $stmt->close();
        session_destroy();
        header("Location: goodbye.html");
        exit();
    } else {
        $message = "Failed to delete account.";
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(to right, #90D5FF, #77B1D4);
            margin: 0;
        }

        .container {
            max-width: 500px;
            margin: 50px auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 2px 2px 12px rgba(0,0,0,0.1);
        }

        h2, h3 {
            text-align: center;
            color: #517891;
        }

        label {
            display: block;
            margin-top: 15px;
        }

        input[type="email"],
        input[type="password"],
        input[type="file"],
        input[type="submit"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            box-sizing: border-box;
        }

        input[type="submit"] {
            background-color: #57B9FF;
            color: white;
            border: none;
            cursor: pointer;
            margin-top: 20px;
            border-radius: 5px;
        }

        input[type="submit"]:hover {
            background-color: #517891;
        }

        .message {
            color: green;
            text-align: center;
            margin-top: 10px;
        }

        .links {
            text-align: center;
            margin-top: 20px;
        }

        .links a {
            color: #517891;
            text-decoration: none;
            font-weight: bold;
        }

        .delete-btn {
            background-color: #FF4B5C;
            color: white;
            margin-top: 30px;
            border-radius: 5px;
        }

        .delete-btn:hover {
            background-color: #D42F3A;
        }
    </style>
</head>
<body>

<?php 
$pageTitle = "Settings";
include 'navbar.php'; 
?>

<div class="container">
    <h2>Settings</h2>

    <?php if ($message): ?>
        <div class="message"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="POST">
        <label for="email">Update Email:</label>
        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>" required>
        <input type="submit" value="Update Email">
    </form>

    <hr>

    <h3>Update Profile Picture:</h3>
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="profile_image" accept="image/*" required>
        <input type="submit" value="Upload Profile Picture">
    </form>

    <hr>

    <h3>Change Password</h3>
    <form method="POST">
        <label for="current_password">Current Password:</label>
        <input type="password" name="current_password" required>

        <label for="new_password">New Password:</label>
        <input type="password" name="new_password" required>

        <label for="confirm_password">Confirm New Password:</label>
        <input type="password" name="confirm_password" required>

        <input type="submit" value="Change Password">
    </form>

    <hr>

    <h3>Delete Account</h3>
    <form method="POST" onsubmit="return confirm('Are you sure you want to delete your account? This action cannot be undone.');">
        <input type="hidden" name="delete_account" value="1">
        <input type="submit" class="delete-btn" value="Delete My Account">
    </form>
</div>

</body>
</html>
