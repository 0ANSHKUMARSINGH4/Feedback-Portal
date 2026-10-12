<?php
require_once 'db.php';
requireAuth();

if (($_SESSION['role'] ?? '') === 'admin') {
    header("Location: admin_dashboard.php");
    exit();
}

$userId = $_SESSION['user_id'];
$message = "";
$messageType = "danger";

// Guard: User must complete trainee registration first
$regStmt = $pdo->prepare("SELECT id FROM complete_info WHERE user_id = ?");
$regStmt->execute([$userId]);
if (!$regStmt->fetch()) {
    header("Location: user_dashboard.php");
    exit();
}

// Guard: Check if feedback already submitted
$feedbackStmt = $pdo->prepare("SELECT id FROM feedback WHERE user_id = ?");
$feedbackStmt->execute([$userId]);
if ($feedbackStmt->fetch()) {
    header("Location: user_dashboard.php");
    exit();
}

$questions = [
    'q1' => '1. Overall Quality: How well did this technical program meet learning expectations?',
    'q2' => '2. Engagement & Faculty: Was the instructor interactive, knowledgeable, and responsive?',
    'q3' => '3. Resources & Labs: Did you receive sufficient workshop materials, equipment, and lab access?',
    'q4' => '4. Practical Industry Relevance: How relevant are the skills acquired to real-world industrial operations?',
    'q5' => '5. Program Organization: Was the training schedule well-structured, clear, and punctual?'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = "Invalid security token. Please refresh the page.";
    } else {
        $department = trim($_POST['department'] ?? '');
        $feedbackMessage = trim($_POST['feedback_message'] ?? '');
        $q1 = intval($_POST['q1'] ?? 0);
        $q2 = intval($_POST['q2'] ?? 0);
        $q3 = intval($_POST['q3'] ?? 0);
        $q4 = intval($_POST['q4'] ?? 0);
        $q5 = intval($_POST['q5'] ?? 0);

        if (empty($department) || empty($feedbackMessage)) {
            $message = "Please select your department and provide your written feedback comments.";
        } elseif ($q1 < 1 || $q1 > 5 || $q2 < 1 || $q2 > 5 || $q3 < 1 || $q3 > 5 || $q4 < 1 || $q4 > 5 || $q5 < 1 || $q5 > 5) {
            $message = "Please rate all 5 evaluation questions between 1 and 5 stars.";
        } else {
            $insert = $pdo->prepare("
                INSERT INTO feedback (user_id, department, feedback_message, q1, q2, q3, q4, q5, submitted_at, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ");

            if ($insert->execute([$userId, $department, $feedbackMessage, $q1, $q2, $q3, $q4, $q5])) {
                try {
                    $log = $pdo->prepare("INSERT INTO logs (user_id, action) VALUES (?, 'Feedback submitted')");
                    $log->execute([$userId]);
                } catch (Exception $e) {}

                header("Location: user_dashboard.php");
                exit();
            } else {
                $message = "Database error while recording feedback. Please try again.";
            }
        }
    }
}

$pageTitle = "Training Feedback Form";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Training Feedback Form | SNTI Feedback Portal</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<main class="portal-container" style="max-width: 800px;">
  <div class="card">
    <div style="text-align: center; margin-bottom: 30px;">
      <span style="display: inline-block; padding: 4px 14px; background: var(--primary-light); color: var(--primary); font-weight: 700; border-radius: var(--radius-full); font-size: 0.8rem; margin-bottom: 12px; text-transform: uppercase;">
        Step 2 Evaluation
      </span>
      <h2 style="font-size: 1.85rem; margin-bottom: 8px;">Technical Training Evaluation</h2>
      <p style="color: var(--text-muted); font-size: 0.95rem;">
        Your feedback is confidential and directly drives curriculum and workshop improvements at SNTI.
      </p>
    </div>

    <?php if (!empty($message)): ?>
      <div class="alert alert-<?php echo $messageType; ?>">
        <span>⚠️</span> <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="feedback_form.php">
      <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

      <div class="form-group">
        <label for="department" class="form-label">Training Department / Division *</label>
        <select name="department" id="department" class="form-control" required>
          <option value="">-- Select Department --</option>
          <option value="Electrical & Automation Maintenance">Electrical & Automation Maintenance</option>
          <option value="Mechanical & Hydraulics Division">Mechanical & Hydraulics Division</option>
          <option value="Iron & Steel Metallurgy Workshop">Iron & Steel Metallurgy Workshop</option>
          <option value="Industrial Safety & Quality Assurance">Industrial Safety & Quality Assurance</option>
          <option value="Digital Operations & IT Systems">Digital Operations & IT Systems</option>
          <option value="Total Quality Management (TQM)">Total Quality Management (TQM)</option>
        </select>
      </div>

      <div style="margin: 32px 0 20px;">
        <h3 style="font-size: 1.15rem; margin-bottom: 6px;">Evaluation Rating Criteria (1 to 5 Stars)</h3>
        <p style="color: var(--text-muted); font-size: 0.875rem;">1 = Unsatisfactory, 3 = Average, 5 = Outstanding</p>
      </div>

      <?php foreach ($questions as $qKey => $qText): ?>
        <div style="background: #f8fafc; border: 1.5px solid var(--border); border-radius: var(--radius-md); padding: 18px 20px; margin-bottom: 18px;">
          <div style="font-weight: 600; font-size: 0.95rem; margin-bottom: 8px; color: var(--text-main);">
            <?php echo htmlspecialchars($qText); ?>
          </div>
          <div class="star-rating">
            <?php for ($i = 5; $i >= 1; $i--): ?>
              <input type="radio" id="<?php echo $qKey; ?>_star<?php echo $i; ?>" name="<?php echo $qKey; ?>" value="<?php echo $i; ?>" required <?php echo (($_POST[$qKey] ?? '') == $i) ? 'checked' : ''; ?>>
              <label for="<?php echo $qKey; ?>_star<?php echo $i; ?>" title="<?php echo $i; ?> Stars">★</label>
            <?php endfor; ?>
          </div>
        </div>
      <?php endforeach; ?>

      <div class="form-group" style="margin-top: 24px;">
        <label for="feedback_message" class="form-label">Written Remarks, Suggestions & Improvements *</label>
        <textarea name="feedback_message" id="feedback_message" class="form-control" placeholder="Share specific thoughts on instructors, workshop practicals, or suggested topics..." required style="min-height: 120px;"><?php echo htmlspecialchars($_POST['feedback_message'] ?? ''); ?></textarea>
      </div>

      <div style="display: flex; gap: 14px; margin-top: 28px;">
        <button type="submit" class="btn btn-primary" style="flex: 2;">
          Submit Confidential Feedback →
        </button>
        <a href="user_dashboard.php" class="btn btn-secondary" style="flex: 1;">
          Back to Dashboard
        </a>
      </div>
    </form>
  </div>
</main>

</body>
</html>
