<?php
require_once 'db.php';

function generateRandomUserId(string $prefix = "USR_"): string {
    return $prefix . bin2hex(random_bytes(4));
}

$pageTitle = "Create Account";
$message = "";
$messageType = "danger";
$initialRole = ($_GET['role'] ?? 'user') === 'admin' ? 'admin' : 'user';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = "Invalid security token. Please refresh the page.";
    } elseif (isRateLimited('signup', 10, 300)) {
        $message = "Too many registration attempts. Please wait 5 minutes.";
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $requestedRole = ($_POST['selected_role'] ?? 'user') === 'admin' ? 'admin' : 'user';
        $adminKey = trim($_POST['admin_key'] ?? '');

        // Expected admin master key (configurable via env ADMIN_SECRET)
        $validAdminKey = getenv('ADMIN_SECRET') ?: 'SNTI_ADMIN_2026';

        if (empty($name) || empty($email) || empty($password)) {
            $message = "All standard registration fields are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Please enter a valid email address.";
        } elseif ($pwdError = validatePasswordPolicy($password)) {
            $message = $pwdError;
        } elseif ($password !== $confirmPassword) {
            $message = "Passwords do not match.";
        } elseif ($requestedRole === 'admin' && $adminKey !== $validAdminKey) {
            // Enterprise RBAC Gate: Admin accounts require verified master authorization key
            $message = "Invalid Administrator Authorization Key. Please enter the correct SNTI master key.";
        } else {
            // Check if email already registered
            $checkStmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
            $checkStmt->execute([$email]);
            if ($checkStmt->fetch()) {
                $message = "An account with this email address already exists.";
            } else {
                do {
                    $userId = generateRandomUserId($requestedRole === 'admin' ? 'ADM_' : 'USR_');
                    $uidStmt = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ?");
                    $uidStmt->execute([$userId]);
                } while ($uidStmt->fetch());

                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $insertStmt = $pdo->prepare("
                    INSERT INTO users (user_id, password, name, email, role, created_at)
                    VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
                ");

                if ($insertStmt->execute([$userId, $hashedPassword, $name, $email, $requestedRole])) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['role'] = $requestedRole;
                    $_SESSION['name'] = $name;
                    $_SESSION['email'] = $email;
                    $_SESSION['created_at'] = date("Y-m-d H:i:s");
                    $_SESSION['last_activity'] = time();

                    try {
                        $logStmt = $pdo->prepare("INSERT INTO logs (user_id, action) VALUES (?, ?)");
                        $logStmt->execute([$userId, "New {$requestedRole} registered"]);
                    } catch (Exception $e) {}

                    header("Location: " . ($requestedRole === 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php'));
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
  <title>Create Account | SNTI Feedback Portal</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<main class="auth-container">
  <div class="card" style="margin-top: 20px;">
    
    <!-- Role Switcher Tabs -->
    <div class="role-tabs">
      <button type="button" class="role-tab-btn <?php echo ($initialRole === 'user') ? 'active' : ''; ?>" id="tabUserSignup" onclick="switchSignupRole('user')">
        🎓 Trainee Registration
      </button>
      <button type="button" class="role-tab-btn <?php echo ($initialRole === 'admin') ? 'active admin-active' : ''; ?>" id="tabAdminSignup" onclick="switchSignupRole('admin')">
        🛡️ Admin Registration
      </button>
    </div>

    <!-- Header Content -->
    <div style="text-align: center; margin-bottom: 24px;">
      <h2 style="font-size: 1.75rem; margin-bottom: 8px;" id="signupTitle">
        <?php echo ($initialRole === 'admin') ? 'Create Administrator Account' : 'Create Trainee Account'; ?>
      </h2>
      <p style="color: var(--text-muted); font-size: 0.95rem;" id="signupSubtitle">
        <?php echo ($initialRole === 'admin') ? 'Requires institutional authorization key for SNTI operations' : 'Join SNTI portal to register & submit training evaluations'; ?>
      </p>
    </div>

    <?php if (!empty($message)): ?>
      <div class="alert alert-<?php echo $messageType; ?>">
        <span>⚠️</span> <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="signup.php" id="signupForm">
      <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
      <input type="hidden" name="selected_role" id="signupRoleInput" value="<?php echo htmlspecialchars($initialRole); ?>">

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
        <div class="password-field-wrapper">
          <input type="password" name="password" id="password" class="form-control" placeholder="Min. 8 chars, 1 uppercase, 1 lowercase, 1 number" required>
          <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('password', this)">👁️</button>
        </div>
      </div>

      <div class="form-group">
        <label for="confirm_password" class="form-label">Confirm Password</label>
        <div class="password-field-wrapper">
          <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Re-enter your password" required>
          <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('confirm_password', this)">👁️</button>
        </div>
      </div>

      <!-- Admin Secret Key Input (Visible only when Admin tab is active) -->
      <div class="form-group" id="adminKeyGroup" style="<?php echo ($initialRole === 'admin') ? 'display: block;' : 'display: none;'; ?> background: #fdf4ff; border: 1.5px solid #f0abfc; padding: 16px; border-radius: var(--radius-md);">
        <label for="admin_key" class="form-label" style="color: #86198f;">
          🛡️ Administrator Master Authorization Key *
        </label>
        <input type="password" name="admin_key" id="admin_key" class="form-control" placeholder="Enter SNTI admin master key" style="border-color: #d8b4fe;">
        <div style="font-size: 0.8rem; color: #701a75; margin-top: 6px;">
          Default evaluation key: <code>SNTI_ADMIN_2026</code>
        </div>
      </div>

      <button type="submit" class="btn <?php echo ($initialRole === 'admin') ? 'btn-admin' : 'btn-primary'; ?> btn-block" id="signupSubmitBtn" style="margin-top: 10px; font-size: 1rem;">
        <?php echo ($initialRole === 'admin') ? 'Register Administrator Account →' : 'Register as Trainee →'; ?>
      </button>
    </form>

    <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border); text-align: center; font-size: 0.9rem; color: var(--text-muted);">
      Already have an account? <a href="login.php" style="color: var(--primary); font-weight: 700; text-decoration: none;">Sign in here</a>
    </div>

  </div>
</main>

<script>
function switchSignupRole(role) {
  const tabUser = document.getElementById('tabUserSignup');
  const tabAdmin = document.getElementById('tabAdminSignup');
  const title = document.getElementById('signupTitle');
  const subtitle = document.getElementById('signupSubtitle');
  const btn = document.getElementById('signupSubmitBtn');
  const roleInput = document.getElementById('signupRoleInput');
  const adminKeyGroup = document.getElementById('adminKeyGroup');
  const adminKeyInput = document.getElementById('admin_key');

  roleInput.value = role;

  if (role === 'admin') {
    tabUser.classList.remove('active');
    tabAdmin.classList.add('active', 'admin-active');
    title.innerText = 'Create Administrator Account';
    subtitle.innerText = 'Requires institutional authorization key for SNTI operations';
    btn.className = 'btn btn-admin btn-block';
    btn.innerText = 'Register Administrator Account →';
    adminKeyGroup.style.display = 'block';
    adminKeyInput.required = true;
  } else {
    tabAdmin.classList.remove('active', 'admin-active');
    tabUser.classList.add('active');
    title.innerText = 'Create Trainee Account';
    subtitle.innerText = 'Join SNTI portal to register & submit training evaluations';
    btn.className = 'btn btn-primary btn-block';
    btn.innerText = 'Register as Trainee →';
    adminKeyGroup.style.display = 'none';
    adminKeyInput.required = false;
  }
}

function togglePasswordVisibility(inputId, btn) {
  const input = document.getElementById(inputId);
  if (input.type === 'password') {
    input.type = 'text';
    btn.innerText = '🔒';
  } else {
    input.type = 'password';
    btn.innerText = '👁️';
  }
}
</script>

</body>
</html>
