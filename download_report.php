<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

include 'db_connection.php';

// Set CSV headers
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=full_report.csv');

// Open output stream
$output = fopen("php://output", "w");

// ==== Section 1: Signed-Up Users ====
fputcsv($output, ['Signed-Up Users']);
fputcsv($output, ['User ID', 'Role', 'Type', 'Created At']);

$users = $conn->query("SELECT user_id, role, created_at FROM users");
while ($row = $users->fetch_assoc()) {
    $type = $row['role'] === 'admin' ? 'Admin' : 'Trainee';
    fputcsv($output, [$row['user_id'], $row['role'], $type, $row['created_at']]);
}

fputcsv($output, []);

// ==== Section 2: Registered Trainees ====
fputcsv($output, ['Registered Trainees']);
fputcsv($output, ['User ID', 'Full Name', 'Branch', 'Gender', 'DOB', 'Contact Number', 'College', 'Address', 'Pincode', 'Email', 'Photo (Filename)', 'Created At']);

$trainees = $conn->query("
    SELECT user_id, full_name, branch, gender, dob, contact_number, college, address, pincode, email, photo, created_at 
    FROM complete_info
    WHERE user_id IN (SELECT user_id FROM users WHERE role = 'user')
");
while ($row = $trainees->fetch_assoc()) {
    fputcsv($output, $row);
}

fputcsv($output, []);

// ==== Section 3: Feedbacks ====
fputcsv($output, ['Feedbacks']);
fputcsv($output, ['User ID', 'Department', 'Feedback Message', 'Q1', 'Q2', 'Q3', 'Q4', 'Q5', 'Submitted At', 'Created At']);

$feedbacks = $conn->query("SELECT user_id, department, feedback_message, q1, q2, q3, q4, q5, submitted_at, created_at FROM feedback");
while ($row = $feedbacks->fetch_assoc()) {
    fputcsv($output, $row);
}

fclose($output);
$conn->close();
exit();
