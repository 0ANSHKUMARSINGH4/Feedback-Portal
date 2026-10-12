<?php
require_once 'db.php';
requireAuth('admin');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=snti_users_export_' . date('Ymd_His') . '.csv');

$out = fopen("php://output", "w");
fputcsv($out, [
    'User ID',
    'Full Name',
    'Email Address',
    'System Role',
    'Trainee Verified',
    'Feedback Submitted',
    'Account Created At'
]);

$query = "
    SELECT 
        u.user_id,
        u.name,
        u.email,
        u.role,
        u.created_at,
        ci.id as trainee_id,
        f.id as feedback_id
    FROM users u
    LEFT JOIN complete_info ci ON u.user_id = ci.user_id
    LEFT JOIN feedback f ON u.user_id = f.user_id
    ORDER BY u.created_at DESC
";
$stmt = $pdo->query($query);
while ($row = $stmt->fetch()) {
    fputcsv($out, [
        $row['user_id'],
        $row['name'],
        $row['email'],
        $row['role'],
        !empty($row['trainee_id']) ? 'Yes' : 'No',
        !empty($row['feedback_id']) ? 'Yes' : 'No',
        $row['created_at']
    ]);
}

fclose($out);
exit();
