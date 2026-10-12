<?php
require_once 'db.php';

$pageTitle = "Log In";
$message = "";
$messageType = "danger";
$initialRole = ($_GET['role'] ?? 'user') === 'admin' ? 'admin' : 'user';

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
        $selectedRole = $_POST['selected_role'] ?? 'user';

        if (empty($email) || empty($password)) {
            $message = "Please enter both your email and password.";
        } else {
            $stmt = $pdo->prepare("SELECT user_id, password, role, created_at, name FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                clearRateLimit('login');
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['created_at'] = $user['created_at'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $email;
                $_SESSION['last_activity'] = time();

                try {
                    $logStmt = $pdo->prepare("INSERT INTO logs (user_id, action) VALUES (?, 'User logged in')");
                    $logStmt->execute([$user['user_id']]);
                } catch (Exception $e) {}

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
  <title>Sign In | SNTI Feedback Portal</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<main class="auth-container">
  <div class="card" style="margin-top: 20px;">
    
    <!-- Portal Role Switcher Tabs -->
    <div class="role-tabs">
      <button type="button" class="role-tab-btn <?php echo ($initialRole === 'user') ? 'active' : ''; ?>" id="tabUser" onclick="switchLoginRole('user')">
        🎓 Trainee Portal
      </button>
      <button type="button" class="role-tab-btn <?php echo ($initialRole === 'admin') ? 'active admin-active' : ''; ?>" id="tabAdmin" onclick="switchLoginRole('admin')">
        🛡️ Admin Control
      </button>
    </div>

    <!-- Header Content -->
    <div style="text-align: center; margin-bottom: 24px;">
      <h2 style="font-size: 1.75rem; margin-bottom: 8px;" id="loginTitle">
        <?php echo ($initialRole === 'admin') ? 'Administrator Login' : 'Trainee Sign In'; ?>
      </h2>
      <p style="color: var(--text-muted); font-size: 0.95rem;" id="loginSubtitle">
        <?php echo ($initialRole === 'admin') ? 'Authorized SNTI Management & Operations Access' : 'Sign in to complete training feedback & verification'; ?>
      </p>
    </div>

    <?php if (!empty($message)): ?>
      <div class="alert alert-<?php echo $messageType; ?>">
        <span>⚠️</span> <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="login.php" id="loginForm">
      <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
      <input type="hidden" name="selected_role" id="selectedRoleInput" value="<?php echo htmlspecialchars($initialRole); ?>">

      <div class="form-group">
        <label for="email" class="form-label">Email Address</label>
        <input type="email" name="email" id="email" class="form-control" placeholder="name@domain.com" required autofocus value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
      </div>

      <div class="form-group">
        <label for="password" class="form-label">Password</label>
        <div class="password-field-wrapper">
          <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
          <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('password', this)" title="Show/Hide Password">👁️</button>
        </div>
      </div>

      <button type="submit" class="btn <?php echo ($initialRole === 'admin') ? 'btn-admin' : 'btn-primary'; ?> btn-block" id="loginSubmitBtn" style="margin-top: 10px; font-size: 1rem;">
        <?php echo ($initialRole === 'admin') ? 'Authenticate as Admin →' : 'Sign In as Trainee →'; ?>
      </button>
    </form>

    <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border); text-align: center; font-size: 0.9rem; color: var(--text-muted);">
      <span id="signupPromptText">New to SNTI?</span>
      <a href="signup.php" id="signupLink" style="color: var(--primary); font-weight: 700; text-decoration: none; margin-left: 4px;">
        Register Trainee Account
      </a>
    </div>

    <!-- Quick 1-Click Demo Fillers -->
    <div class="demo-credentials-card">
      <div style="font-weight: 700; color: var(--text-main); margin-bottom: 6px; display: flex; align-items: center; justify-content: space-between;">
        <span>⚡ Quick Demo Credentials</span>
        <span style="font-size: 0.75rem; color: var(--text-light);">1-Click Fill</span>
      </div>
      <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <button type="button" class="demo-btn-pill" onclick="fillCredentials('admin@feedbackportal.local', 'Admin@12345', 'admin')">
          🛡️ Admin Demo
        </button>
        <button type="button" class="demo-btn-pill" onclick="fillCredentials('qwe@gmail.com', 'Admin@12345', 'user')">
          🎓 Sample Trainee
        </button>
      </div>
    </div>

  </div>
</main>

<script>
function switchLoginRole(role) {
  const tabUser = document.getElementById('tabUser');
  const tabAdmin = document.getElementById('tabAdmin');
  const title = document.getElementById('loginTitle');
  const subtitle = document.getElementById('loginSubtitle');
  const btn = document.getElementById('loginSubmitBtn');
  const roleInput = document.getElementById('selectedRoleInput');
  const signupLink = document.getElementById('signupLink');
  const signupPrompt = document.getElementById('signupPromptText');

  roleInput.value = role;

  if (role === 'admin') {
    tabUser.classList.remove('active');
    tabAdmin.classList.add('active', 'admin-active');
    title.innerText = 'Administrator Login';
    subtitle.innerText = 'Authorized SNTI Management & Quality Operations';
    btn.className = 'btn btn-admin btn-block';
    btn.innerText = 'Authenticate as Admin →';
    signupPrompt.innerText = 'Need Admin Access?';
    signupLink.href = 'signup.php?role=admin';
    signupLink.innerText = 'Register with Admin Key';
  } else {
    tabAdmin.classList.remove('active', 'admin-active');
    tabUser.classList.add('active');
    title.innerText = 'Trainee Sign In';
    subtitle.innerText = 'Sign in to complete training feedback & verification';
    btn.className = 'btn btn-primary btn-block';
    btn.innerText = 'Sign In as Trainee →';
    signupPrompt.innerText = 'New to SNTI?';
    signupLink.href = 'signup.php';
    signupLink.innerText = 'Register Trainee Account';
  }
}

function fillCredentials(email, password, role) {
  document.getElementById('email').value = email;
  document.getElementById('password').value = password;
  switchLoginRole(role);
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
