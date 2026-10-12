<?php
require_once 'db.php';
requireAuth('admin');

$roleFilter = $_GET['role'] ?? 'all';
$sql = "
    SELECT 
        u.user_id,
        u.name,
        u.email,
        u.role,
        u.created_at,
        ci.id as trainee_reg_id,
        ci.branch,
        f.id as feedback_id
    FROM users u
    LEFT JOIN complete_info ci ON u.user_id = ci.user_id
    LEFT JOIN feedback f ON u.user_id = f.user_id
";

$params = [];
if ($roleFilter === 'user') {
    $sql .= " WHERE u.role = 'user'";
} elseif ($roleFilter === 'admin') {
    $sql .= " WHERE u.role = 'admin'";
}
$sql .= " ORDER BY u.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = "Audit Registered Users";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Accounts | SNTI Admin</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<main class="portal-container">
  <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 28px;">
    <div>
      <h1 style="font-size: 1.85rem; margin-bottom: 6px;">Registered User Accounts</h1>
      <p style="color: var(--text-muted); font-size: 0.95rem;">
        Total Signups: <strong><?php echo count($users); ?></strong> &bull; Portal account credentials audit
      </p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
      <a href="download_signedup_users.php" class="btn btn-primary">
        📥 Export Users CSV
      </a>
      <a href="admin_dashboard.php" class="btn btn-secondary">
        ← Back to Dashboard
      </a>
    </div>
  </div>

  <!-- Role Filter Bar -->
  <div class="card" style="padding: 16px 24px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
    <div style="font-weight: 600; font-size: 0.95rem;">Filter by Account Role:</div>
    <div style="display: flex; gap: 10px;">
      <a href="view_signed_up_users.php?role=all" class="btn <?php echo ($roleFilter === 'all') ? 'btn-primary' : 'btn-secondary'; ?>" style="padding: 6px 16px; font-size: 0.85rem;">
        All Accounts
      </a>
      <a href="view_signed_up_users.php?role=user" class="btn <?php echo ($roleFilter === 'user') ? 'btn-primary' : 'btn-secondary'; ?>" style="padding: 6px 16px; font-size: 0.85rem;">
        Trainees
      </a>
      <a href="view_signed_up_users.php?role=admin" class="btn <?php echo ($roleFilter === 'admin') ? 'btn-primary' : 'btn-secondary'; ?>" style="padding: 6px 16px; font-size: 0.85rem;">
        Administrators
      </a>
    </div>
  </div>

  <div class="table-wrapper">
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>User Account</th>
            <th>Role</th>
            <th>Trainee Registration</th>
            <th>Feedback Status</th>
            <th>Joined Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <tr>
              <td>
                <strong><?php echo htmlspecialchars($u['name']); ?></strong>
                <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($u['email']); ?></div>
                <div style="font-size: 0.75rem; color: var(--text-light);"><?php echo htmlspecialchars($u['user_id']); ?></div>
              </td>
              <td>
                <?php if ($u['role'] === 'admin'): ?>
                  <span style="background: #ede9fe; color: #6d28d9; padding: 4px 10px; border-radius: var(--radius-full); font-size: 0.75rem; font-weight: 700;">
                    Admin
                  </span>
                <?php else: ?>
                  <span style="background: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: var(--radius-full); font-size: 0.75rem; font-weight: 700;">
                    Trainee
                  </span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($u['role'] === 'admin'): ?>
                  <span style="color: var(--text-light);">—</span>
                <?php elseif (!empty($u['trainee_reg_id'])): ?>
                  <span style="color: var(--success); font-weight: 600; font-size: 0.85rem;">
                    ✓ Registered (<?php echo htmlspecialchars($u['branch'] ?? 'N/A'); ?>)
                  </span>
                <?php else: ?>
                  <span style="color: var(--warning); font-size: 0.85rem;">
                    Pending Step 1
                  </span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($u['role'] === 'admin'): ?>
                  <span style="color: var(--text-light);">—</span>
                <?php elseif (!empty($u['feedback_id'])): ?>
                  <span style="color: var(--success); font-weight: 600; font-size: 0.85rem;">
                    ✓ Submitted
                  </span>
                <?php else: ?>
                  <span style="color: var(--text-light); font-size: 0.85rem;">
                    Not Submitted
                  </span>
                <?php endif; ?>
              </td>
              <td style="white-space: nowrap; font-size: 0.85rem; color: var(--text-muted);">
                <?php echo date("d M Y", strtotime($u['created_at'])); ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

</body>
</html>
