<?php
require_once 'db.php';
requireAuth('admin');

// Single efficient query using LEFT JOIN to eliminate N+1 problem and join on user_id
$query = "
    SELECT 
        ci.*,
        u.email as account_email,
        u.created_at as account_created,
        f.id as feedback_id,
        f.submitted_at as feedback_submitted
    FROM complete_info ci
    LEFT JOIN users u ON ci.user_id = u.user_id
    LEFT JOIN feedback f ON ci.user_id = f.user_id
    ORDER BY ci.created_at DESC
";
$stmt = $pdo->query($query);
$trainees = $stmt->fetchAll();

$pageTitle = "Registered Trainees";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registered Trainees | SNTI Admin</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<main class="portal-container">
  <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 28px;">
    <div>
      <h1 style="font-size: 1.85rem; margin-bottom: 6px;">Registered Trainees</h1>
      <p style="color: var(--text-muted); font-size: 0.95rem;">
        Total Trainees: <strong><?php echo count($trainees); ?></strong> &bull; Verified academic trainee profiles
      </p>
    </div>
    <div style="display: flex; gap: 10px;">
      <a href="download_trainees.php" class="btn btn-primary">
        📥 Export Trainees CSV
      </a>
      <a href="admin_dashboard.php" class="btn btn-secondary">
        ← Back to Dashboard
      </a>
    </div>
  </div>

  <?php if (empty($trainees)): ?>
    <div class="card" style="text-align: center; padding: 50px; color: var(--text-muted);">
      No trainees have completed academic registration yet.
    </div>
  <?php else: ?>
    <div class="table-wrapper">
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Trainee Details</th>
              <th>Branch</th>
              <th>College</th>
              <th>Gender & DOB</th>
              <th>Contact Phone</th>
              <th>Feedback Status</th>
              <th>Registered At</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($trainees as $t): ?>
              <tr>
                <td>
                  <strong><?php echo htmlspecialchars($t['name']); ?></strong>
                  <div style="font-size: 0.75rem; color: var(--text-light);"><?php echo htmlspecialchars($t['user_id']); ?></div>
                  <div style="font-size: 0.75rem; color: var(--text-light);"><?php echo htmlspecialchars($t['account_email'] ?? ''); ?></div>
                </td>
                <td>
                  <span style="display: inline-block; padding: 3px 8px; background: #e0f2fe; color: #0369a1; border-radius: 4px; font-weight: 600; font-size: 0.8rem;">
                    <?php echo htmlspecialchars($t['branch'] ?? 'N/A'); ?>
                  </span>
                </td>
                <td style="max-width: 220px; font-size: 0.875rem;">
                  <?php echo htmlspecialchars($t['college_name'] ?? 'N/A'); ?>
                </td>
                <td style="font-size: 0.85rem;">
                  <?php echo htmlspecialchars($t['gender'] ?? 'N/A'); ?><br>
                  <small style="color: var(--text-muted);"><?php echo htmlspecialchars($t['dob'] ?? 'N/A'); ?></small>
                </td>
                <td style="font-size: 0.85rem;">
                  <?php echo htmlspecialchars($t['phone'] ?? 'N/A'); ?>
                </td>
                <td>
                  <?php if (!empty($t['feedback_id'])): ?>
                    <span style="background: var(--success-bg); color: var(--success); font-weight: 700; font-size: 0.75rem; padding: 4px 10px; border-radius: var(--radius-full);">
                      ✓ Submitted
                    </span>
                  <?php else: ?>
                    <span style="background: var(--warning-bg); color: var(--warning); font-weight: 700; font-size: 0.75rem; padding: 4px 10px; border-radius: var(--radius-full);">
                      Pending
                    </span>
                  <?php endif; ?>
                </td>
                <td style="white-space: nowrap; font-size: 0.8rem; color: var(--text-muted);">
                  <?php echo date("d M Y", strtotime($t['created_at'])); ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</main>

</body>
</html>
