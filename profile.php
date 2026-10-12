<?php
require_once 'db.php';
requireAuth();

$userId = $_SESSION['user_id'];
$message = "";
$messageType = "success";

// Fetch current user details
$userStmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$userStmt->execute([$userId]);
$user = $userStmt->fetch();

// Fetch trainee complete info if available
$traineeStmt = $pdo->prepare("SELECT * FROM complete_info WHERE user_id = ?");
$traineeStmt->execute([$userId]);
$trainee = $traineeStmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = "Invalid security token. Please refresh and try again.";
        $messageType = "danger";
    } else {
        $action = $_POST['action'] ?? '';

        // 1. Update Profile Image
        if ($action === 'update_avatar' && isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['profile_image']['tmp_name'];
            $name = $_FILES['profile_image']['name'];
            $size = $_FILES['profile_image']['size'];
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp']) || @getimagesize($tmp) === false) {
                $message = "Invalid image file. Only verified JPG, PNG, and WebP images are allowed.";
                $messageType = "danger";
            } elseif ($size > 2 * 1024 * 1024) {
                $message = "Image must be under 2MB.";
                $messageType = "danger";
            } else {
                $targetDir = __DIR__ . '/uploads/';
                if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
                $avatarFile = 'avatar_' . bin2hex(random_bytes(8)) . '.' . $ext;
                if (move_uploaded_file($tmp, $targetDir . $avatarFile)) {
                    $update = $pdo->prepare("UPDATE users SET profile_image = ? WHERE user_id = ?");
                    $update->execute([$avatarFile, $userId]);
                    $user['profile_image'] = $avatarFile;
                    $message = "Profile picture updated successfully.";
                } else {
                    $message = "Failed to upload image.";
                    $messageType = "danger";
                }
            }
        }

        // 2. Update Email (Requires Password Confirmation - Vulnerability #15)
        elseif ($action === 'update_email') {
            $newEmail = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));
            $emailPassword = $_POST['email_password'] ?? '';

            if (empty($emailPassword) || !password_verify($emailPassword, $user['password'])) {
                $message = "Current password is required to update your email address.";
                $messageType = "danger";
            } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                $message = "Please enter a valid email address.";
                $messageType = "danger";
            } elseif ($newEmail === $user['email']) {
                $message = "New email matches your current email.";
                $messageType = "danger";
            } else {
                $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
                $check->execute([$newEmail, $userId]);
                if ($check->fetch()) {
                    $message = "This email is already in use by another account.";
                    $messageType = "danger";
                } else {
                    $up = $pdo->prepare("UPDATE users SET email = ? WHERE user_id = ?");
                    $up->execute([$newEmail, $userId]);
                    $_SESSION['email'] = $newEmail;
                    $user['email'] = $newEmail;
                    $message = "Email address updated successfully.";
                }
            }
        }

        // 3. Update Password (Enforces Password Policy - Vulnerability #8)
        elseif ($action === 'change_password') {
            $curPass = $_POST['current_password'] ?? '';
            $newPass = $_POST['new_password'] ?? '';
            $confPass = $_POST['confirm_password'] ?? '';

            if (empty($curPass) || empty($newPass) || empty($confPass)) {
                $message = "Please fill in all password fields.";
                $messageType = "danger";
            } elseif (!password_verify($curPass, $user['password'])) {
                $message = "Current password is incorrect.";
                $messageType = "danger";
            } elseif ($newPass !== $confPass) {
                $message = "New passwords do not match.";
                $messageType = "danger";
            } elseif ($pwdErr = validatePasswordPolicy($newPass)) {
                $message = $pwdErr;
                $messageType = "danger";
            } else {
                $newHash = password_hash($newPass, PASSWORD_DEFAULT);
                $up = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $up->execute([$newHash, $userId]);
                $user['password'] = $newHash;
                $message = "Password changed successfully.";
            }
        }

        // 4. Delete Account
        elseif ($action === 'delete_account') {
            $confirmPass = $_POST['confirm_delete_password'] ?? '';
            if (!password_verify($confirmPass, $user['password'])) {
                $message = "Incorrect password. Account deletion aborted.";
                $messageType = "danger";
            } else {
                // Wipe user, feedback, complete_info
                $pdo->prepare("DELETE FROM feedback WHERE user_id = ?")->execute([$userId]);
                $pdo->prepare("DELETE FROM complete_info WHERE user_id = ?")->execute([$userId]);
                $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$userId]);

                session_destroy();
                header("Location: goodbye.html");
                exit();
            }
        }
    }
}

$pageTitle = "Profile & Settings";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile & Settings | SNTI Feedback Portal</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<main class="portal-container" style="max-width: 800px;">
  <div style="margin-bottom: 28px;">
    <h1 style="font-size: 1.85rem; margin-bottom: 6px;">Profile & Account Settings</h1>
    <p style="color: var(--text-muted); font-size: 0.95rem;">Manage your credentials, profile image, and portal security</p>
  </div>

  <?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
      <span><?php echo ($messageType === 'success') ? '✓' : '⚠️'; ?></span>
      <?php echo htmlspecialchars($message); ?>
    </div>
  <?php endif; ?>

  <!-- Identity Card -->
  <div class="card" style="margin-bottom: 24px; display: flex; align-items: center; gap: 24px; flex-wrap: wrap;">
    <div style="position: relative;">
      <?php if (!empty($user['profile_image']) && file_exists(__DIR__ . '/uploads/' . $user['profile_image'])): ?>
        <img src="uploads/<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile" style="width: 84px; height: 84px; border-radius: var(--radius-full); object-fit: cover; border: 3px solid var(--primary);">
      <?php else: ?>
        <div style="width: 84px; height: 84px; border-radius: var(--radius-full); background: var(--primary-gradient); color: #fff; font-size: 2rem; font-weight: 800; display: flex; align-items: center; justify-content: center;">
          <?php echo strtoupper(substr($user['name'] ?? 'U', 0, 1)); ?>
        </div>
      <?php endif; ?>
    </div>

    <div style="flex: 1;">
      <h2 style="font-size: 1.35rem; margin-bottom: 4px;"><?php echo htmlspecialchars($user['name']); ?></h2>
      <p style="color: var(--text-muted); font-size: 0.9rem;">
        <?php echo htmlspecialchars($user['email']); ?> &bull; Role: <strong><?php echo ucfirst($user['role']); ?></strong>
      </p>
      <p style="color: var(--text-light); font-size: 0.8rem; margin-top: 4px;">
        Account ID: <code><?php echo htmlspecialchars($user['user_id']); ?></code> &bull; Member since: <?php echo date("d M Y", strtotime($user['created_at'])); ?>
      </p>
    </div>

    <div>
      <form method="POST" enctype="multipart/form-data" style="display: flex; gap: 8px;">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <input type="hidden" name="action" value="update_avatar">
        <label for="profile_image_input" class="btn btn-secondary" style="cursor: pointer; padding: 8px 14px; font-size: 0.85rem;">
          📷 Change Photo
        </label>
        <input type="file" id="profile_image_input" name="profile_image" accept="image/*" style="display: none;" onchange="this.form.submit()">
      </form>
    </div>
  </div>

  <!-- Trainee Details Card (if registered) -->
  <?php if (!empty($trainee)): ?>
    <div class="card" style="margin-bottom: 24px;">
      <h3 style="font-size: 1.15rem; margin-bottom: 14px;">Academic Trainee Information</h3>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; font-size: 0.9rem;">
        <div><strong>Branch:</strong> <?php echo htmlspecialchars($trainee['branch'] ?? 'N/A'); ?></div>
        <div><strong>College:</strong> <?php echo htmlspecialchars($trainee['college_name'] ?? 'N/A'); ?></div>
        <div><strong>Contact Phone:</strong> <?php echo htmlspecialchars($trainee['phone'] ?? 'N/A'); ?></div>
        <div><strong>Date of Birth:</strong> <?php echo htmlspecialchars($trainee['dob'] ?? 'N/A'); ?></div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Settings Grid -->
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">

    <!-- Email Update -->
    <div class="card">
      <h3 style="font-size: 1.15rem; margin-bottom: 14px;">Update Email Address</h3>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <input type="hidden" name="action" value="update_email">

        <div class="form-group">
          <label for="email" class="form-label">New Email Address</label>
          <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>

        <div class="form-group">
          <label for="email_password" class="form-label">Current Password (Required to confirm)</label>
          <input type="password" name="email_password" id="email_password" class="form-control" placeholder="Enter your current password" required>
        </div>

        <button type="submit" class="btn btn-secondary btn-block">Confirm & Update Email</button>
      </form>
    </div>

    <!-- Password Change -->
    <div class="card">
      <h3 style="font-size: 1.15rem; margin-bottom: 14px;">Change Password</h3>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <input type="hidden" name="action" value="change_password">

        <div class="form-group">
          <label class="form-label">Current Password</label>
          <input type="password" name="current_password" class="form-control" required>
        </div>

        <div class="form-group">
          <label class="form-label">New Password</label>
          <input type="password" name="new_password" class="form-control" placeholder="Min. 6 chars" required>
        </div>

        <div class="form-group">
          <label class="form-label">Confirm New Password</label>
          <input type="password" name="confirm_password" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Update Password</button>
      </form>
    </div>
  </div>

  <!-- Account Deletion Zone -->
  <div class="card" style="margin-top: 24px; border: 1.5px solid var(--danger-bg); background: #fffafb;">
    <h3 style="font-size: 1.15rem; color: var(--danger); margin-bottom: 8px;">Danger Zone: Delete Account</h3>
    <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 16px;">
      Permanently delete your account, academic verification records, and submitted feedbacks. This action cannot be undone.
    </p>

    <form method="POST" onsubmit="return confirm('Are you sure you want to permanently delete your account?');">
      <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
      <input type="hidden" name="action" value="delete_account">

      <div class="form-group" style="max-width: 320px;">
        <label class="form-label">Enter Password to Confirm Deletion</label>
        <input type="password" name="confirm_delete_password" class="form-control" placeholder="Current password" required>
      </div>

      <button type="submit" class="btn btn-danger">
        Permanently Delete My Account
      </button>
    </form>
  </div>
</main>

</body>
</html>
