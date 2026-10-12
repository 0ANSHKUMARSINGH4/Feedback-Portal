<?php
require_once 'db.php';
requireAuth('admin');

$query = "
    SELECT f.*, u.name as trainee_name, u.email as trainee_email 
    FROM feedback f 
    LEFT JOIN users u ON f.user_id = u.user_id 
    ORDER BY f.submitted_at DESC
";
$stmt = $pdo->query($query);
$feedbacks = $stmt->fetchAll();

// CSV Export Handler
if (isset($_GET['download']) && $_GET['download'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=snti_feedback_responses_' . date('Ymd_His') . '.csv');
    
    $out = fopen('php://output', 'w');
    fputcsv($out, ['User ID', 'Trainee Name', 'Trainee Email', 'Department', 'Feedback Remarks', 'Q1 (Quality)', 'Q2 (Engagement)', 'Q3 (Resources)', 'Q4 (Relevance)', 'Q5 (Organization)', 'Average Score', 'Submitted At']);

    foreach ($feedbacks as $row) {
        $avg = round(($row['q1'] + $row['q2'] + $row['q3'] + $row['q4'] + $row['q5']) / 5.0, 2);
        fputcsv($out, [
            $row['user_id'],
            $row['trainee_name'] ?? 'N/A',
            $row['trainee_email'] ?? 'N/A',
            $row['department'],
            $row['feedback_message'],
            $row['q1'],
            $row['q2'],
            $row['q3'],
            $row['q4'],
            $row['q5'],
            $avg,
            $row['submitted_at']
        ]);
    }
    fclose($out);
    exit();
}

$pageTitle = "Training Feedback Responses";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Feedback Responses | SNTI Admin</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<main class="portal-container">
  <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 28px;">
    <div>
      <h1 style="font-size: 1.85rem; margin-bottom: 6px;">Trainee Feedback Responses</h1>
      <p style="color: var(--text-muted); font-size: 0.95rem;">
        Total Responses: <strong><?php echo count($feedbacks); ?></strong> &bull; Complete training evaluations
      </p>
    </div>
    <div style="display: flex; gap: 10px;">
      <a href="view_feedbacks.php?download=csv" class="btn btn-primary">
        📥 Export Feedback CSV
      </a>
      <a href="admin_dashboard.php" class="btn btn-secondary">
        ← Back to Dashboard
      </a>
    </div>
  </div>

  <?php if (empty($feedbacks)): ?>
    <div class="card" style="text-align: center; padding: 50px; color: var(--text-muted);">
      No feedback evaluations recorded in the system yet.
    </div>
  <?php else: ?>
    <div class="table-wrapper">
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Trainee</th>
              <th>Department</th>
              <th>Score</th>
              <th>Remarks</th>
              <th>Q1</th>
              <th>Q2</th>
              <th>Q3</th>
              <th>Q4</th>
              <th>Q5</th>
              <th>Submitted</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($feedbacks as $fb): 
              $avg = round(($fb['q1'] + $fb['q2'] + $fb['q3'] + $fb['q4'] + $fb['q5']) / 5.0, 1);
            ?>
              <tr>
                <td>
                  <strong><?php echo htmlspecialchars($fb['trainee_name'] ?? 'Unknown'); ?></strong>
                  <div style="font-size: 0.75rem; color: var(--text-light);"><?php echo htmlspecialchars($fb['user_id']); ?></div>
                  <div style="font-size: 0.75rem; color: var(--text-light);"><?php echo htmlspecialchars($fb['trainee_email'] ?? ''); ?></div>
                </td>
                <td><?php echo htmlspecialchars($fb['department']); ?></td>
                <td>
                  <span style="color: #f59e0b; font-weight: 700;">★ <?php echo $avg; ?></span>
                </td>
                <td style="max-width: 280px; min-width: 200px;">
                  <div style="font-size: 0.875rem; color: #334155; line-height: 1.5;">
                    <?php echo nl2br(htmlspecialchars($fb['feedback_message'])); ?>
                  </div>
                </td>
                <td><?php echo $fb['q1']; ?></td>
                <td><?php echo $fb['q2']; ?></td>
                <td><?php echo $fb['q3']; ?></td>
                <td><?php echo $fb['q4']; ?></td>
                <td><?php echo $fb['q5']; ?></td>
                <td style="white-space: nowrap; font-size: 0.8rem; color: var(--text-muted);">
                  <?php echo date("d M Y, h:i A", strtotime($fb['submitted_at'])); ?>
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
