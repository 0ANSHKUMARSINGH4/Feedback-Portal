<?php
require_once 'db.php';
requireAuth('admin');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=snti_full_audit_report_' . date('Ymd_His') . '.csv');

$out = fopen("php://output", "w");

// === Section 1: All User Accounts ===
fputcsv($out, ['=== SECTION 1: USER ACCOUNTS AUDIT ===']);
fputcsv($out, ['User ID', 'Full Name', 'Email', 'Role', 'Account Created']);
$users = $pdo->query("SELECT user_id, name, email, role, created_at FROM users ORDER BY created_at DESC");
while ($row = $users->fetch()) {
    fputcsv($out, [$row['user_id'], $row['name'], $row['email'], $row['role'], $row['created_at']]);
}
fputcsv($out, []);

// === Section 2: Registered Trainees ===
fputcsv($out, ['=== SECTION 2: VERIFIED REGISTERED TRAINEES ===']);
fputcsv($out, ['User ID', 'Trainee Name', 'Branch', 'Gender', 'DOB', 'Contact Phone', 'College / University', 'Address', 'Pincode', 'Registered Date']);
$trainees = $pdo->query("SELECT user_id, name, branch, gender, dob, phone, college_name, address, pincode, created_at FROM complete_info ORDER BY created_at DESC");
while ($row = $trainees->fetch()) {
    fputcsv($out, [
        $row['user_id'],
        $row['name'],
        $row['branch'],
        $row['gender'],
        $row['dob'],
        $row['phone'],
        $row['college_name'],
        $row['address'],
        $row['pincode'],
        $row['created_at']
    ]);
}
fputcsv($out, []);

// === Section 3: Trainee Feedback Evaluations ===
fputcsv($out, ['=== SECTION 3: TRAINING EVALUATION RESPONSES ===']);
fputcsv($out, ['User ID', 'Department', 'Written Feedback Remarks', 'Q1 (Session Quality)', 'Q2 (Engagement)', 'Q3 (Resources & Labs)', 'Q4 (Industry Relevance)', 'Q5 (Organization)', 'Average Score', 'Submitted At']);
$feedbacks = $pdo->query("SELECT user_id, department, feedback_message, q1, q2, q3, q4, q5, submitted_at FROM feedback ORDER BY submitted_at DESC");
while ($row = $feedbacks->fetch()) {
    $avg = round(($row['q1'] + $row['q2'] + $row['q3'] + $row['q4'] + $row['q5']) / 5.0, 2);
    fputcsv($out, [
        $row['user_id'],
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
