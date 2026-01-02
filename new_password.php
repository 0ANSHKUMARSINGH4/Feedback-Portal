<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['token'])) {
    $token = $_GET['token'];

    // Validate token
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE reset_token = ? AND token_expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->bind_result($user_id);
    
    if ($stmt->fetch()) {
        // Show reset form
        ?>
        <form action="new_password.php" method="POST">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <label>New Password:</label>
            <input type="password" name="new_password" required>
            <button type="submit">Reset Password</button>
        </form>
        <?php
    } else {
        echo "Invalid or expired token.";
    }
    $stmt->close();

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

    // Update password
    $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, token_expiry = NULL WHERE reset_token = ?");
    $stmt->bind_param("ss", $new_password, $token);
    if ($stmt->execute()) {
        echo "Password has been reset. <a href='login.html'>Login</a>";
    } else {
        echo "Error updating password.";
    }
    $stmt->close();
}
?>
