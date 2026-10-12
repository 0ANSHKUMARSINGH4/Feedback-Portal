<?php
require_once 'db.php';

function generateRandomUserId(string $prefix = "USR_"): string {
    return $prefix . bin2hex(random_bytes(4));
}

$pageTitle = "Create Account";
$message = "";
$messageType = "danger";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = "Invalid security token. Please refresh the page.";
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($name) || empty($email) || empty($password)) {
            $message = "All fields are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Please enter a valid email address.";
        } elseif (strlen($password) < 6) {
            $message = "Password must be at least 6 characters.";
        } elseif ($password !== $confirmPassword) {
            $message = "Passwords do not match.";
        } else {
            // Check if email already registered
            $checkStmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
            $checkStmt->execute([$email]);
            if ($checkStmt->fetch()) {
                $message = "An account with this email address already exists.";
            } else {
                // Generate unique user_id
                do {
                    $userId = generateRandomUserId();
                    $uidStmt = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ?");
                    $uidStmt->execute([$userId]);
                } while ($uidStmt->fetch());

                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $role = 'user'; // Hardcode trainee user role to prevent privilege escalation

                $insertStmt = $pdo->prepare("
                    INSERT INTO users (user_id, password, name, email, role, created_at)
                    VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
                ");

                if ($insertStmt->execute([$userId, $hashedPassword, $name, $email, $role])) {
                    // Log user in automatically
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['role'] = $role;
                    $_SESSION['name'] = $name;
                    $_SESSION['email'] = $email;
                    $_SESSION['created_at'] = date("Y-m-d H:i:s");

                    try {
                        $logStmt = $pdo->prepare("INSERT INTO logs (user_id, action) VALUES (?, 'New user registered')");
                        $logStmt->execute([$userId]);
                    } catch (Exception $e) {}

                    header("Location: user_dashboard.php");
                    exit();
                } else {
                    $message = "Account creation failed. Please try again.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up | SNTI Feedback Portal</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<main class="auth-container">
  <div class="card" style="margin-top: 20px;">
    <div style="text-align: center; margin-bottom: 28px;">
      <h2 style="font-size: 1.75rem; margin-bottom: 8px;">Create Trainee Account</h2>
      <p style="color: var(--text-muted); font-size: 0.95rem;">Join the SNTI portal to register & submit feedback</p>
    </div>

    <?php if (!empty($message)): ?>
      <div class="alert alert-<?php echo $messageType; ?>">
        <span>⚠️</span> <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="signup.php">
      <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

      <div class="form-group">
        <label for="name" class="form-label">Full Name</label>
        <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Rahul Sharma" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
      </div>

      <div class="form-group">
        <label for="email" class="form-label">Email Address</label>
        <input type="email" name="email" id="email" class="form-control" placeholder="name@domain.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
      </div>

      <div class="form-group">
        <label for="password" class="form-label">Password</label>
        <input type="password" name="password" id="password" class="form-control" placeholder="At least 6 characters" required>
      </div>

      <div class="form-group">
        <label for="confirm_password" class="form-label">Confirm Password</label>
        <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Re-enter your password" required>
      </div>

      <button type="submit" class="btn btn-primary btn-block" style="margin-top: 10px;">
        Register as Trainee
      </button>
    </form>

    <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border); text-align: center; font-size: 0.9rem; color: var(--text-muted);">
      Already have an account? <a href="login.php" style="color: var(--primary); font-weight: 700; text-decoration: none;">Sign in here</a>
    </div>
  </div>
</main>

</body>
</html>
