<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $new_name = trim($_POST['name']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];

    // Fetch current password hash from DB
    $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $stmt->bind_result($hashed_password);
    $stmt->fetch();
    $stmt->close();

    // Verify current password
    if (!password_verify($current_password, $hashed_password)) {
        $_SESSION['update_message'] = "Incorrect current password.";
        header("Location: settings.php");
        exit();
    }

    // Build query based on new password presence
    if (!empty($new_password)) {
        $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET name = ?, password = ? WHERE user_id = ?");
        $stmt->bind_param("sss", $new_name, $new_hashed_password, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET name = ? WHERE user_id = ?");
        $stmt->bind_param("ss", $new_name, $user_id);
    }

    $stmt->execute();
    $stmt->close();

    $_SESSION['name'] = $new_name;
    $_SESSION['update_message'] = "Profile updated successfully!";
    header("Location: settings.php");
    exit();
} else {
    header("Location: settings.php");
    exit();
}
?>
