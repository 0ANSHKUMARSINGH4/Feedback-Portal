<?php
require_once 'db.php';
requireAuth('admin');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=snti_trainees_export_' . date('Ymd_His') . '.csv');

$out = fopen("php://output", "w");
fputcsv($out, [
    'User ID',
    'Trainee Name',
    'Account Email',
    'Branch',
    'Gender',
    'DOB',
    'Contact Phone',
    'College Name',
    'Address',
    'Pincode',
    'Feedback Submitted',
    'Registered Date'
]);

$query = "
    SELECT 
        ci.*,
        u.email as account_email,
        f.id as feedback_id
    FROM complete_info ci
    LEFT JOIN users u ON ci.user_id = u.user_id
    LEFT JOIN feedback f ON ci.user_id = f.user_id
    ORDER BY ci.created_at DESC
";
$stmt = $pdo->query($query);
while ($row = $stmt->fetch()) {
    fputcsv($out, [
        $row['user_id'],
        $row['name'],
        $row['account_email'] ?? 'N/A',
        $row['branch'],
        $row['gender'],
        $row['dob'],
        $row['phone'],
        $row['college_name'],
        $row['address'],
        $row['pincode'],
        !empty($row['feedback_id']) ? 'Yes' : 'No',
        $row['created_at']
    ]);
}

fclose($out);
exit();
