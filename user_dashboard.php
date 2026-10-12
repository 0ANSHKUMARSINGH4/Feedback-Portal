<?php
require_once 'db.php';
requireAuth();

// If user is admin, redirect to admin dashboard
if (($_SESSION['role'] ?? '') === 'admin') {
    header("Location: admin_dashboard.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Fetch current user details
$userStmt = $pdo->prepare("SELECT name, email, created_at, profile_image FROM users WHERE user_id = ?");
$userStmt->execute([$userId]);
$user = $userStmt->fetch();
$name = $user['name'] ?? $_SESSION['name'];
$createdAt = $user['created_at'] ?? 'Recently';

// Check if trainee registration completed
$traineeStmt = $pdo->prepare("SELECT * FROM complete_info WHERE user_id = ?");
$traineeStmt->execute([$userId]);
$traineeInfo = $traineeStmt->fetch();
$isRegistered = !empty($traineeInfo);

// Check if feedback submitted
$feedbackStmt = $pdo->prepare("SELECT * FROM feedback WHERE user_id = ?");
$feedbackStmt->execute([$userId]);
$feedback = $feedbackStmt->fetch();
$hasFeedback = !empty($feedback);

$pageTitle = "Trainee Dashboard";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Trainee Dashboard | SNTI Feedback Portal</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<main class="portal-container">
  <!-- Welcome Banner -->
  <div class="card" style="margin-bottom: 30px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px;">
    <div>
      <h1 style="font-size: 1.85rem; margin-bottom: 6px;">
        Welcome back, <?php echo htmlspecialchars($name); ?>! 👋
      </h1>
      <p style="color: var(--text-muted); font-size: 0.95rem;">
        Trainee ID: <code style="background: #e2e8f0; padding: 2px 6px; border-radius: 4px;"><?php echo htmlspecialchars($userId); ?></code> &bull; Enrolled: <?php echo date("d M Y", strtotime($createdAt)); ?>
      </p>
    </div>
    <div>
      <span class="btn btn-secondary" style="pointer-events: none; border-color: var(--primary); color: var(--primary); font-weight: 700;">
        Role: Trainee
      </span>
    </div>
  </div>

  <!-- Action Steps Grid -->
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; margin-bottom: 36px;">

    <!-- Step 1: Trainee Verification -->
    <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
      <div>
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
          <span style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--primary);">
            Step 1
          </span>
          <?php if ($isRegistered): ?>
            <span style="background: var(--success-bg); color: var(--success); font-weight: 700; font-size: 0.75rem; padding: 4px 10px; border-radius: var(--radius-full);">
              ✓ Completed
            </span>
          <?php else: ?>
            <span style="background: var(--warning-bg); color: var(--warning); font-weight: 700; font-size: 0.75rem; padding: 4px 10px; border-radius: var(--radius-full);">
              Pending Verification
            </span>
          <?php endif; ?>
        </div>

        <h3 style="font-size: 1.25rem; margin-bottom: 10px;">Trainee Academic Registration</h3>
        <p style="color: var(--text-muted); font-size: 0.925rem; line-height: 1.6; margin-bottom: 20px;">
          Provide your engineering branch, college background, contact number, and verification details to enable feedback submission.
        </p>

        <?php if ($isRegistered): ?>
          <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 14px; font-size: 0.875rem; margin-bottom: 20px;">
            <p><strong>Branch:</strong> <?php echo htmlspecialchars($traineeInfo['branch'] ?? 'N/A'); ?></p>
            <p><strong>College:</strong> <?php echo htmlspecialchars($traineeInfo['college_name'] ?? 'N/A'); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($traineeInfo['phone'] ?? 'N/A'); ?></p>
          </div>
        <?php endif; ?>
      </div>

      <div>
        <?php if ($isRegistered): ?>
          <button class="btn btn-secondary btn-block disabled" disabled>
            Registration Complete
          </button>
        <?php else: ?>
          <a href="registration.php" class="btn btn-primary btn-block">
            Complete Registration Form →
          </a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Step 2: Training Feedback -->
    <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
      <div>
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
          <span style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--primary);">
            Step 2
          </span>
          <?php if ($hasFeedback): ?>
            <span style="background: var(--success-bg); color: var(--success); font-weight: 700; font-size: 0.75rem; padding: 4px 10px; border-radius: var(--radius-full);">
              ✓ Submitted
            </span>
          <?php elseif ($isRegistered): ?>
            <span style="background: var(--primary-light); color: var(--primary); font-weight: 700; font-size: 0.75rem; padding: 4px 10px; border-radius: var(--radius-full);">
              Ready to Submit
            </span>
          <?php else: ?>
            <span style="background: #f1f5f9; color: var(--text-light); font-weight: 700; font-size: 0.75rem; padding: 4px 10px; border-radius: var(--radius-full);">
              Locked
            </span>
          <?php endif; ?>
        </div>

        <h3 style="font-size: 1.25rem; margin-bottom: 10px;">Training Experience Feedback</h3>
        <p style="color: var(--text-muted); font-size: 0.925rem; line-height: 1.6; margin-bottom: 20px;">
          Rate course organization, faculty engagement, and practical workshop modules. Your evaluation helps improve future training programs.
        </p>
      </div>

      <div>
        <?php if (!$isRegistered): ?>
          <button class="btn btn-secondary btn-block disabled" disabled>
            Complete Step 1 to Unlock
          </button>
        <?php elseif ($hasFeedback): ?>
          <button class="btn btn-secondary btn-block disabled" disabled>
            Feedback Already Submitted
          </button>
        <?php else: ?>
          <a href="feedback_form.php" class="btn btn-primary btn-block">
            Submit Training Feedback →
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Submitted Feedback Review Card if available -->
  <?php if ($hasFeedback): ?>
    <div class="card">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
        <h3 style="font-size: 1.35rem;">Your Submitted Feedback</h3>
        <span style="color: var(--text-muted); font-size: 0.875rem;">
          Submitted on: <?php echo date("d M Y, h:i A", strtotime($feedback['submitted_at'])); ?>
        </span>
      </div>

      <div style="background: #f8fafc; border-radius: var(--radius-md); padding: 20px; border: 1px solid var(--border); margin-bottom: 24px;">
        <p style="font-size: 0.95rem; margin-bottom: 12px;">
          <strong>Assigned Department:</strong> <?php echo htmlspecialchars($feedback['department']); ?>
        </p>
        <p style="font-size: 0.95rem; margin-bottom: 8px;">
          <strong>Written Remarks:</strong>
        </p>
        <div style="background: #ffffff; padding: 14px; border-radius: var(--radius-sm); border: 1px solid var(--border); font-style: italic; color: #334155;">
          "<?php echo nl2br(htmlspecialchars($feedback['feedback_message'])); ?>"
        </div>
      </div>

      <h4 style="font-size: 1rem; margin-bottom: 14px; text-transform: uppercase; letter-spacing: 0.04em; color: var(--text-muted);">
        Detailed Ratings Breakdown (1 to 5)
      </h4>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px;">
        <?php
        $qTitles = [
            'q1' => 'Session Quality',
            'q2' => 'Interactivity',
            'q3' => 'Resource Allocation',
            'q4' => 'Practical Relevance',
            'q5' => 'Course Structure'
        ];
        foreach ($qTitles as $key => $title):
            $score = intval($feedback[$key] ?? 0);
        ?>
          <div style="background: #ffffff; border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 14px; text-align: center;">
            <div style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px;"><?php echo $title; ?></div>
            <div style="font-size: 1.4rem; color: #f59e0b; font-weight: 800;">
              <?php echo str_repeat('★', $score) . str_repeat('☆', 5 - $score); ?>
            </div>
            <small style="color: var(--text-muted); font-weight: 600;"><?php echo $score; ?> / 5</small>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

</main>

</body>
</html>
