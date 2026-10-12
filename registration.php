<?php
require_once 'db.php';
requireAuth();

// If user is admin, redirect to admin dashboard
if (($_SESSION['role'] ?? '') === 'admin') {
    header("Location: admin_dashboard.php");
    exit();
}

$userId = $_SESSION['user_id'];
$message = "";
$messageType = "danger";

// Check if already registered
$checkStmt = $pdo->prepare("SELECT id FROM complete_info WHERE user_id = ?");
$checkStmt->execute([$userId]);
if ($checkStmt->fetch()) {
    header("Location: user_dashboard.php");
    exit();
}

// Fetch current user details
$userStmt = $pdo->prepare("SELECT name, email FROM users WHERE user_id = ?");
$userStmt->execute([$userId]);
$currentUser = $userStmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = "Invalid security token. Please refresh the page.";
    } else {
        $branch = trim($_POST['branch'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $dob = trim($_POST['dob'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $college = trim($_POST['college'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $pincode = trim($_POST['pincode'] ?? '');

        if (empty($branch) || empty($gender) || empty($dob) || empty($phone) || empty($college) || empty($address) || empty($pincode)) {
            $message = "Please fill in all required registration fields.";
        } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
            $message = "Contact phone number must be exactly 10 digits.";
        } elseif (!preg_match('/^[0-9]{6}$/', $pincode)) {
            $message = "Pincode must be exactly 6 digits.";
        } else {
            // Handle optional photo upload safely
            $photoFilename = null;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $fileTmp = $_FILES['photo']['tmp_name'];
                $fileName = $_FILES['photo']['name'];
                $fileSize = $_FILES['photo']['size'];
                $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];

                if (!in_array($ext, $allowed) || @getimagesize($fileTmp) === false) {
                    $message = "Invalid image file. Only verified JPG, PNG, and WebP images are allowed.";
                } elseif ($fileSize > 2 * 1024 * 1024) {
                    $message = "Photo exceeds maximum limit of 2MB.";
                } else {
                    $targetDir = __DIR__ . '/uploads/';
                    if (!is_dir($targetDir)) {
                        mkdir($targetDir, 0755, true);
                    }
                    $photoFilename = 'trainee_' . bin2hex(random_bytes(8)) . '.' . $ext;
                    if (!move_uploaded_file($fileTmp, $targetDir . $photoFilename)) {
                        $photoFilename = null;
                    }
                }
            }

            if (empty($message)) {
                $insertStmt = $pdo->prepare("
                    INSERT INTO complete_info 
                    (user_id, name, branch, gender, dob, phone, college_name, address, pincode, photo, is_registered, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, CURRENT_TIMESTAMP)
                ");

                if ($insertStmt->execute([
                    $userId,
                    $currentUser['name'],
                    $branch,
                    $gender,
                    $dob,
                    $phone,
                    $college,
                    $address,
                    $pincode,
                    $photoFilename
                ])) {
                    // Update user's profile image if uploaded
                    if ($photoFilename !== null) {
                        $updateImg = $pdo->prepare("UPDATE users SET profile_image = ? WHERE user_id = ?");
                        $updateImg->execute([$photoFilename, $userId]);
                    }

                    try {
                        $logStmt = $pdo->prepare("INSERT INTO logs (user_id, action) VALUES (?, 'Trainee profile completed')");
                        $logStmt->execute([$userId]);
                    } catch (Exception $e) {}

                    header("Location: user_dashboard.php");
                    exit();
                } else {
                    $message = "Failed to save registration details. Please try again.";
                }
            }
        }
    }
}

$pageTitle = "Trainee Registration";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Trainee Registration | SNTI Feedback Portal</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<main class="portal-container" style="max-width: 760px;">
  <div class="card">
    <div style="text-align: center; margin-bottom: 28px;">
      <span style="display: inline-block; padding: 4px 14px; background: var(--primary-light); color: var(--primary); font-weight: 700; border-radius: var(--radius-full); font-size: 0.8rem; margin-bottom: 12px; text-transform: uppercase;">
        Step 1 Verification
      </span>
      <h2 style="font-size: 1.75rem; margin-bottom: 8px;">Trainee Academic Registration</h2>
      <p style="color: var(--text-muted); font-size: 0.95rem;">
        Fill in your institutional background to verify training eligibility at SNTI.
      </p>
    </div>

    <?php if (!empty($message)): ?>
      <div class="alert alert-<?php echo $messageType; ?>">
        <span>⚠️</span> <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="registration.php" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px;">
        <div class="form-group">
          <label class="form-label">Full Name</label>
          <input type="text" class="form-control" value="<?php echo htmlspecialchars($currentUser['name'] ?? ''); ?>" disabled style="background: #f1f5f9;">
        </div>

        <div class="form-group">
          <label class="form-label">Registered Email</label>
          <input type="email" class="form-control" value="<?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>" disabled style="background: #f1f5f9;">
        </div>
      </div>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px;">
        <div class="form-group">
          <label for="branch" class="form-label">Engineering Branch *</label>
          <select name="branch" id="branch" class="form-control" required>
            <option value="">-- Select Branch --</option>
            <option value="Computer Science & Engineering">Computer Science & Engineering</option>
            <option value="Electrical Engineering">Electrical Engineering</option>
            <option value="Electronics & Communication">Electronics & Communication</option>
            <option value="Mechanical Engineering">Mechanical Engineering</option>
            <option value="Metallurgical & Materials">Metallurgical & Materials</option>
            <option value="Civil Engineering">Civil Engineering</option>
            <option value="Automobile Engineering">Automobile Engineering</option>
          </select>
        </div>

        <div class="form-group">
          <label for="gender" class="form-label">Gender *</label>
          <select name="gender" id="gender" class="form-control" required>
            <option value="">-- Select Gender --</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
            <option value="Other">Other</option>
          </select>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px;">
        <div class="form-group">
          <label for="dob" class="form-label">Date of Birth *</label>
          <input type="date" name="dob" id="dob" class="form-control" required>
        </div>

        <div class="form-group">
          <label for="phone" class="form-label">Contact Number *</label>
          <input type="tel" name="phone" id="phone" class="form-control" placeholder="10-digit mobile number" pattern="[0-9]{10}" required>
        </div>
      </div>

      <div class="form-group">
        <label for="college" class="form-label">College / University Name *</label>
        <input type="text" name="college" id="college" class="form-control" placeholder="e.g. National Institute of Technology, Jamshedpur" required>
      </div>

      <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 18px;">
        <div class="form-group">
          <label for="address" class="form-label">Residential Address *</label>
          <input type="text" name="address" id="address" class="form-control" placeholder="Street, City, State" required>
        </div>

        <div class="form-group">
          <label for="pincode" class="form-label">Pincode *</label>
          <input type="text" name="pincode" id="pincode" class="form-control" placeholder="6-digit pincode" pattern="[0-9]{6}" required>
        </div>
      </div>

      <div class="form-group">
        <label for="photo" class="form-label">Upload Trainee ID Photo (Optional, Max 2MB)</label>
        <input type="file" name="photo" id="photo" class="form-control" accept="image/jpeg,image/png,image/webp">
      </div>

      <div style="display: flex; gap: 14px; margin-top: 24px;">
        <button type="submit" class="btn btn-primary" style="flex: 2;">
          Save & Complete Trainee Registration →
        </button>
        <a href="user_dashboard.php" class="btn btn-secondary" style="flex: 1;">
          Cancel
        </a>
      </div>
    </form>
  </div>
</main>

</body>
</html>
