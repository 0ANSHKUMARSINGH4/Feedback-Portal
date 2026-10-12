<?php
require_once 'db.php';
requireAuth('admin');

$adminId = $_SESSION['user_id'];
$adminName = $_SESSION['name'] ?? 'Admin';

// Key Metrics
$totalFeedbacks = (int)$pdo->query("SELECT COUNT(*) FROM feedback")->fetchColumn();
$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalTrainees = (int)$pdo->query("SELECT COUNT(*) FROM complete_info")->fetchColumn();

// Average satisfaction score across all feedbacks
$avgScore = 0;
if ($totalFeedbacks > 0) {
    $avgQuery = $pdo->query("SELECT AVG((q1 + q2 + q3 + q4 + q5) / 5.0) as overall_avg FROM feedback");
    $avgScore = round((float)$avgQuery->fetchColumn(), 1);
}

// Recent feedbacks snippet
$recentStmt = $pdo->query("
    SELECT f.*, u.name as user_name 
    FROM feedback f 
    LEFT JOIN users u ON f.user_id = u.user_id 
    ORDER BY f.submitted_at DESC 
    LIMIT 5
");
$recentFeedbacks = $recentStmt->fetchAll();

$pageTitle = "Administrator Dashboard";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard | SNTI Feedback Portal</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<main class="portal-container">
  <!-- Header Bar -->
  <div class="card" style="margin-bottom: 30px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px;">
    <div>
      <h1 style="font-size: 1.85rem; margin-bottom: 6px;">
        Administrator Control Center
      </h1>
      <p style="color: var(--text-muted); font-size: 0.95rem;">
        Welcome, <?php echo htmlspecialchars($adminName); ?> &bull; SNTI Training Analytics & Trainee Operations
      </p>
    </div>
    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
      <a href="download_report.php" class="btn btn-primary">
        📥 Download Full CSV Report
      </a>
    </div>
  </div>

  <!-- Analytics Stats Grid -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-title">Feedback Responses</div>
      <div class="stat-value"><?php echo $totalFeedbacks; ?></div>
      <a href="view_feedbacks.php" class="btn btn-secondary btn-block" style="padding: 8px 14px; font-size: 0.85rem;">
        View All Responses →
      </a>
    </div>

    <div class="stat-card">
      <div class="stat-title">Verified Trainees</div>
      <div class="stat-value"><?php echo $totalTrainees; ?></div>
      <a href="view_users.php" class="btn btn-secondary btn-block" style="padding: 8px 14px; font-size: 0.85rem;">
        Manage Trainees →
      </a>
    </div>

    <div class="stat-card">
      <div class="stat-title">Registered Accounts</div>
      <div class="stat-value"><?php echo $totalUsers; ?></div>
      <a href="view_signed_up_users.php" class="btn btn-secondary btn-block" style="padding: 8px 14px; font-size: 0.85rem;">
        Audit User Accounts →
      </a>
    </div>

    <div class="stat-card">
      <div class="stat-title">Overall Satisfaction</div>
      <div class="stat-value" style="color: #f59e0b;">
        <?php echo $avgScore > 0 ? $avgScore . ' ★' : 'N/A'; ?>
      </div>
      <span style="font-size: 0.825rem; color: var(--text-muted); text-align: center;">
        Across 5 rating parameters
      </span>
    </div>
  </div>

  <!-- Recent Submissions Overview Table -->
  <div class="card">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
      <h3 style="font-size: 1.25rem;">Recent Training Submissions</h3>
      <a href="view_feedbacks.php" style="color: var(--primary); font-weight: 600; text-decoration: none; font-size: 0.9rem;">
        View full log →
      </a>
    </div>

    <?php if (empty($recentFeedbacks)): ?>
      <div style="text-align: center; padding: 40px; color: var(--text-muted);">
        No trainee feedback submitted yet.
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Trainee</th>
              <th>Department</th>
              <th>Avg Score</th>
              <th>Feedback Preview</th>
              <th>Submitted At</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentFeedbacks as $row): 
              $score = round(($row['q1'] + $row['q2'] + $row['q3'] + $row['q4'] + $row['q5']) / 5.0, 1);
            ?>
              <tr>
                <td>
                  <strong><?php echo htmlspecialchars($row['user_name'] ?? $row['user_id']); ?></strong>
                  <div style="font-size: 0.75rem; color: var(--text-light);"><?php echo htmlspecialchars($row['user_id']); ?></div>
                </td>
                <td><?php echo htmlspecialchars($row['department']); ?></td>
                <td>
                  <span style="color: #f59e0b; font-weight: 700;">★ <?php echo $score; ?></span> / 5
                </td>
                <td style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                  <?php echo htmlspecialchars($row['feedback_message']); ?>
                </td>
                <td><?php echo date("d M Y, h:i A", strtotime($row['submitted_at'])); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</main>

</body>
</html>
