<?php
require_once 'db.php';

$pageTitle = "Log In";
$message = "";
$messageType = "danger";

if (isset($_GET['msg']) && $_GET['msg'] === 'timeout') {
    $message = "Your session expired due to inactivity. Please sign in again.";
    $messageType = "info";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = "Invalid security token. Please refresh and try again.";
    } elseif (isRateLimited('login', 5, 300)) {
        $message = "Too many failed login attempts. Please wait 5 minutes before trying again.";
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $message = "Please enter both your email and password.";
        } else {
            $stmt = $pdo->prepare("SELECT user_id, password, role, created_at, name FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                clearRateLimit('login');
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['created_at'] = $user['created_at'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $email;
                $_SESSION['last_activity'] = time();

                // Log authentication action
                try {
                    $logStmt = $pdo->prepare("INSERT INTO logs (user_id, action) VALUES (?, 'User logged in')");
                    $logStmt->execute([$user['user_id']]);
                } catch (Exception $e) {
                    // Suppress logging error
                }

                header("Location: " . ($user['role'] === "admin" ? "admin_dashboard.php" : "user_dashboard.php"));
                exit();
            } else {
                recordFailedAttempt('login');
                $message = "Invalid email or password. Please try again.";
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
  <title>Log In | SNTI Feedback Portal</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<main class="auth-container">
  <div class="card" style="margin-top: 20px;">
    <div style="text-align: center; margin-bottom: 28px;">
      <h2 style="font-size: 1.75rem; margin-bottom: 8px;">Welcome Back</h2>
      <p style="color: var(--text-muted); font-size: 0.95rem;">Enter your credentials to access your portal</p>
    </div>

    <?php if (!empty($message)): ?>
      <div class="alert alert-<?php echo $messageType; ?>">
        <span>⚠️</span> <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
      <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

      <div class="form-group">
        <label for="email" class="form-label">Email Address</label>
        <input type="email" name="email" id="email" class="form-control" placeholder="name@domain.com" required autofocus value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
      </div>

      <div class="form-group">
        <label for="password" class="form-label">Password</label>
        <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
      </div>

      <button type="submit" class="btn btn-primary btn-block" style="margin-top: 10px;">
        Sign In
      </button>
    </form>

    <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border); text-align: center; font-size: 0.9rem; color: var(--text-muted);">
      Don't have an account yet? <a href="signup.php" style="color: var(--primary); font-weight: 700; text-decoration: none;">Create one here</a>
    </div>

    <div style="margin-top: 16px; background: #f8fafc; padding: 12px; border-radius: var(--radius-sm); border: 1px dashed var(--border); font-size: 0.8rem; color: var(--text-muted);">
      <strong>Demo Admin:</strong> <code>admin@feedbackportal.local</code> / <code>Admin@12345</code>
    </div>
  </div>
</main>

</body>
</html>
